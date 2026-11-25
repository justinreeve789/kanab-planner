<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Services\Routing\Leg;
use App\Services\Routing\TravelTimes;
use Carbon\CarbonImmutable;

class DriveTime extends BaseTool
{
    public function name(): string
    {
        return 'drive_time';
    }

    public function description(): string
    {
        return 'Driving time and miles between two places (trails, restaurants, places, areas, the traveler\'s '
            . 'lodging or coordinates), with road closures avoided and delays for roadwork or incidents on that date. '
            . 'Doesn\'t include park entrance lines or shuttle waits; mention those for Zion in busy season.';
    }

    public function parameters(): array
    {
        return self::object([
            'from' => self::object(Geo::placeSchema()),
            'to' => self::object(Geo::placeSchema()),
            'depart_at' => ['type' => 'string', 'description' => 'Local date and time, e.g. 2026-12-02 07:30. Default now.'],
        ], ['from', 'to']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['from' => 'required|array', 'to' => 'required|array', 'depart_at' => 'nullable|date']);
        $from = Geo::resolve($args['from'], $context);
        $to = Geo::resolve($args['to'], $context);
        if (!$from || !$to) {
            return ToolResult::unavailable('Could not locate ' . (!$from ? 'the starting point' : 'the destination') . '.');
        }
        $depart = isset($args['depart_at']) ? CarbonImmutable::parse($args['depart_at'], config('chat.timezone')) : $context->now;
        $leg = TravelTimes::make()->leg($from['point'], $to['point'], $depart);

        $arrive = $depart->addMinutes($leg->minutes);
        return ToolResult::ok([
            'from' => $from['label'],
            'to' => $to['label'],
            'minutes' => $leg->minutes,
            'drive' => intdiv($leg->minutes, 60) ? sprintf('%d h %02d min', intdiv($leg->minutes, 60), $leg->minutes % 60) : "{$leg->minutes} min",
            'miles' => round($leg->miles, 1),
            'leave' => $depart->format('D g:i A'),
            'arrive_about' => $arrive->format('D g:i A'),
            'how_computed' => $leg->source === Leg::SOURCE_ROUTER ? 'street routing' : 'estimate from straight-line distance (routing unavailable)',
            'road_notes' => $leg->notes ?: null,
        ], [ToolResult::source('Drive time ' . ($leg->source === Leg::SOURCE_ROUTER ? '(routed)' : '(estimated)'), null, ToolResult::OUR_DATA)]);
    }
}
