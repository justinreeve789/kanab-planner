<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Models\Itinerary\Itinerary;
use App\Models\Trail;

class GetItinerary extends BaseTool
{
    public function name(): string
    {
        return 'get_itinerary';
    }

    public function description(): string
    {
        return 'The traveler\'s own itinerary: dates, base and lodging, and each day\'s area and timed activities '
            . '(with record ids, drive times and notes), plus its share link. Only the itinerary they have open.';
    }

    public function parameters(): array
    {
        return self::object([
            'day' => ['type' => 'string', 'description' => 'Optional: one date (YYYY-MM-DD) or day number.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['day' => 'nullable|string|max:12']);
        if (!$context->itinerary) {
            return ToolResult::unavailable('The traveler has no itinerary open in this chat. Offer to plan one, or ask them to open theirs.');
        }
        $itinerary = Itinerary::withSchedule()->find($context->itinerary->id);
        $base = $itinerary->getBaseArea();

        $days = [];
        foreach ($itinerary->days as $day) {
            if (!empty($args['day']) && $args['day'] !== $day->date && $args['day'] !== (string) $day->day_num) {
                continue;
            }
            $activities = [];
            foreach ($day->periods as $period) {
                foreach ($period->activities as $activity) {
                    $resource = $activity->resource;
                    $activities[] = array_filter([
                        'activity_id' => $activity->id,
                        'start' => $activity->start_time ? date('g:i A', strtotime($activity->start_time)) : null,
                        'end' => $activity->end_time ? date('g:i A', strtotime($activity->end_time)) : null,
                        'what' => $resource?->name ?? $activity->name,
                        'record' => $activity->resource_table ? ['table' => $activity->resource_table, 'id' => $activity->resource_id] : null,
                        'trail_miles' => $resource instanceof Trail ? $resource->distance_miles : null,
                        'drive_before_minutes' => $activity->travel_minutes ?: null,
                        'drive_notes' => $activity->travel_notes ?: null,
                    ], fn($v) => $v !== null && $v !== []);
                }
            }
            $days[] = [
                'day_id' => $day->id,
                'day' => $day->day_num,
                'date' => $day->date,
                'weekday' => date('l', strtotime($day->date)),
                'area_id' => $day->area_id,
                'area' => $day->area?->name,
                'activities' => $activities,
                'note' => $day->getPreamble(),
            ];
        }

        return ToolResult::ok([
            'itinerary_id' => $itinerary->id,
            'dates' => "{$itinerary->start_date} to {$itinerary->end_date}",
            'base' => $base?->name,
            'lodging' => $itinerary->lodging_name,
            'check_in' => $itinerary->check_in_time,
            'check_out' => $itinerary->check_out_time,
            'share_link' => rtrim((string) config('app.url'), '/') . '/' . $itinerary->id,
            'can_edit' => $context->canEditItinerary,
            'days' => $days,
        ], [ToolResult::source('Your itinerary', rtrim((string) config('app.url'), '/') . '/' . $itinerary->id, ToolResult::OUR_DATA)]);
    }
}
