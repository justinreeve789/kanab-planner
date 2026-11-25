<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

class Crowds extends BaseTool
{
    public function name(): string
    {
        return 'crowds';
    }

    public function description(): string
    {
        return 'How busy a restaurant or place usually is, hour by hour, on a given weekday (from popular-times data), '
            . 'with the busiest and quietest hours. Use for waits, "when to go to avoid crowds" and timing. Trails have '
            . 'no busyness data; for them, use solitude scores from search_trails and blog crowd reports.';
    }

    public function parameters(): array
    {
        return self::object([
            'restaurant_id' => ['type' => 'integer'],
            'location_id' => ['type' => 'integer'],
            'date' => ['type' => 'string', 'description' => 'A date (YYYY-MM-DD) or weekday name. Default today.'],
            'hour' => ['type' => 'integer', 'description' => '0-23, to single out one hour.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'restaurant_id' => 'nullable|integer|required_without:location_id',
            'location_id' => 'nullable|integer',
            'date' => 'nullable|string|max:20',
            'hour' => 'nullable|integer|min:0|max:23',
        ]);
        if (!empty($args['restaurant_id'])) {
            $restaurant = Restaurant::find($args['restaurant_id']);
            [$locationId, $name] = [$restaurant?->location_id, $restaurant?->name];
        } else {
            $location = Location::find($args['location_id']);
            [$locationId, $name] = [$location?->id, $location?->name];
        }
        if (!$locationId) {
            return ToolResult::unavailable('That record was not found.');
        }

        $date = $this->date($args['date'] ?? null, $context);
        $weekday = (int) $date->format('w');
        $rows = DB::table('resource_busy')->where(['resource_table' => 'locations', 'resource_id' => $locationId, 'day_of_week' => $weekday])
            ->orderByDesc('collected_date')->get()->unique('hour')->sortBy('hour');
        if ($rows->isEmpty()) {
            return ToolResult::unavailable("No busyness data for {$name}.");
        }
        $open = $rows->filter(fn($r) => $r->percentage > 0);
        $byHour = $rows->mapWithKeys(fn($r) => [date('g A', mktime($r->hour, 0)) => $r->percentage . '%'])->all();

        return ToolResult::ok(array_filter([
            'place' => $name,
            'weekday' => $date->format('l'),
            'busyness_by_hour' => $byHour,
            'at_requested_hour' => isset($args['hour']) ? ($rows->firstWhere('hour', $args['hour'])->percentage ?? 0) . '%' : null,
            'busiest_hour' => $open->isNotEmpty() ? date('g A', mktime($open->sortByDesc('percentage')->first()->hour, 0)) : null,
            'quietest_open_hour' => $open->isNotEmpty() ? date('g A', mktime($open->sortBy('percentage')->first()->hour, 0)) : null,
            'collected' => $rows->first()->collected_date,
            'how_to_read' => 'Percent of this place\'s usual peak, not a wait time.',
        ], fn($v) => $v !== null), [ToolResult::source("Popular times for {$name}", null, ToolResult::OUR_DATA, $rows->first()->collected_date)]);
    }
}
