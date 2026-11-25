<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Live\UsgsClient;
use Carbon\CarbonImmutable;

class RiverFlow extends BaseTool
{
    public function __construct(private UsgsClient $usgs)
    {
    }

    public function name(): string
    {
        return 'river_flow';
    }

    public function description(): string
    {
        return 'Current river flow (cubic feet per second) from USGS gauges, with what it means for hikes in the river: '
            . 'the Zion Narrows closes above 150 CFS and top-down trips above 120 CFS. Use for The Narrows, Orderville '
            . 'and other Virgin River hikes. Gauges: ' . implode(', ', array_keys(config('chat.gauges'))) . '.';
    }

    public function parameters(): array
    {
        return self::object([
            'gauge' => ['type' => 'string', 'enum' => array_keys(config('chat.gauges'))],
            'trail_id' => ['type' => 'integer', 'description' => 'Pick the gauge for this trail.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['gauge' => 'nullable|string', 'trail_id' => 'nullable|integer']);
        $gauges = config('chat.gauges');
        $key = $args['gauge'] ?? null;
        if (!$key && !empty($args['trail_id']) && ($trail = $this->trail($args['trail_id']))) {
            foreach ($gauges as $candidate => $gauge) {
                foreach ($gauge['match'] as $word) {
                    if (mb_stripos($trail->name . ' ' . $trail->name_alt, $word) !== false) {
                        $key = $candidate;
                        break 2;
                    }
                }
            }
            if (!$key) {
                return ToolResult::unavailable('No river gauge applies to this trail.');
            }
        }
        $key ??= array_key_first($gauges);
        if (!isset($gauges[$key])) {
            return ToolResult::unavailable("Unknown gauge {$key}.");
        }
        $gauge = $gauges[$key];

        $reading = $this->usgs->latestDischarge($gauge['site']);
        if (!$reading) {
            return ToolResult::unavailable('The USGS river gauge could not be reached right now.', $gauge['official_url'], ['gauge_page' => UsgsClient::pageFor($gauge['site'])]);
        }

        $applies = array_values(array_filter($gauge['thresholds'], fn($t) => $reading['cfs'] > $t['cfs']));
        $time = $reading['time'] ? CarbonImmutable::parse($reading['time'])->setTimezone(config('chat.timezone')) : null;
        return ToolResult::ok([
            'gauge' => $gauge['name'],
            'cfs' => $reading['cfs'],
            'measured_at' => $time?->format('M j, g:i A'),
            'over_thresholds' => array_column($applies, 'meaning'),
            'thresholds' => $gauge['thresholds'],
            'note' => 'Flash floods can come from storms far upstream; check the weather warnings too.',
        ], [
            ToolResult::source($gauge['name'] . ' (USGS)', UsgsClient::pageFor($gauge['site']), ToolResult::LIVE, $time?->format('M j, g:i A')),
            ToolResult::source('Zion National Park: The Narrows', $gauge['official_url'], ToolResult::OFFICIAL),
        ]);
    }
}
