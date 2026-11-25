<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Models\RoadEvent;

class RoadEvents extends BaseTool
{
    public function name(): string
    {
        return 'road_events';
    }

    public function description(): string
    {
        return 'Road closures, roadwork and incidents from the Utah (UDOT) and Arizona (AZ511) feeds on a date, around '
            . 'one place or along the way between two places. For dirt roads to trailheads, also check trail_details '
            . '(vehicle needed) and trail_knowledge (topic access).';
    }

    public function parameters(): array
    {
        return self::object([
            'place' => self::object(Geo::placeSchema()),
            'to' => self::object(Geo::placeSchema() + []) + ['description' => 'Optional second place: events along the way.'],
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default today.'],
            'within_miles' => ['type' => 'number', 'description' => 'Default 20.'],
        ], ['place']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['place' => 'required|array', 'to' => 'nullable|array', 'date' => 'nullable|date', 'within_miles' => 'nullable|numeric|min:1|max:100']);
        $a = Geo::resolve($args['place'], $context);
        $b = !empty($args['to']) ? Geo::resolve($args['to'], $context) : $a;
        if (!$a || !$b) {
            return ToolResult::unavailable('Could not locate that place.');
        }
        $date = $this->date($args['date'] ?? null, $context)->toDateString();
        $events = RoadEvent::activeOn($date)->around($a['point'], $b['point'], $args['within_miles'] ?? 20)->limit(15)->get();

        return ToolResult::ok([
            'around' => $a['label'] . ($b !== $a ? ' to ' . $b['label'] : ''),
            'date' => $date,
            'events' => $events->map(fn(RoadEvent $e) => array_filter([
                'summary' => $e->summary(),
                'full_closure' => (bool) $e->is_full_closure,
                'type' => $e->event_type,
                'road' => $e->road_name ?? null,
                'starts' => $e->starts_at?->setTimezone(config('chat.timezone'))->format('M j g:i A'),
                'ends' => $e->ends_at?->setTimezone(config('chat.timezone'))->format('M j g:i A'),
                'source' => $e->source ?? null,
            ], fn($v) => $v !== null))->values()->all(),
            'note' => $events->isEmpty() ? 'No reported events. Feeds cover state highways, not dirt roads to trailheads.' : null,
        ], [ToolResult::source('UDOT and AZ511 road events', 'https://udottraffic.utah.gov/', ToolResult::LIVE)]);
    }
}
