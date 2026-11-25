<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\TripShape;
use App\Models\TrailProfile;

class TrailProfileTool extends BaseTool
{
    public function name(): string
    {
        return 'trail_profile';
    }

    public function description(): string
    {
        return 'Measured steepness from the trail\'s GPS track: total climb and descent, the steepest 0.05, 0.1 and 0.25 '
            . 'mile and where they start (mile marker from the trailhead), every section over 15% and over 25% grade, '
            . 'the longest continuous climb, share of the trail that is gentle (under 8%), and the round-trip totals. '
            . 'Use for "how steep", "where is the hard part", "is the climbing at the start". Grades are percent (rise over run).';
    }

    public function parameters(): array
    {
        return self::object([
            'trail_id' => ['type' => 'integer'],
            'include_elevation_series' => ['type' => 'boolean', 'description' => 'Add [mile, feet] points (for charts).'],
        ], ['trail_id']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['trail_id' => 'required|integer', 'include_elevation_series' => 'nullable|boolean']);
        if (!($trail = $this->trail($args['trail_id']))) {
            return $this->notFound('trail', $args['trail_id']);
        }
        $profile = TrailProfile::forTrail($trail->id);
        if (!$profile) {
            return ToolResult::unavailable(
                'No GPS track with elevation for this trail, so steep sections are unknown. Use the listed climb from trail_details and blog reports instead.',
                $trail->url,
                ['listed_climb_ft' => $trail->incline_ft, 'distance_miles' => $trail->distance_miles]
            );
        }

        $series = $profile['profile'];
        unset($profile['profile']);
        $data = [
            'trail' => html_entity_decode($trail->name, ENT_QUOTES),
            'measured' => $profile,
            'as_hiked' => TripShape::for($trail, $profile),
            'how_to_read' => 'Mile markers count from the start of the GPS track (usually the trailhead). "direction" is relative to walking out from the start; on the way back an "up" section is a descent.',
        ];
        if (!empty($args['include_elevation_series'])) {
            $data['elevation_series_mile_ft'] = $series;
        }
        return ToolResult::ok($data, [ToolResult::source(html_entity_decode($trail->name, ENT_QUOTES) . ' GPS track profile', $trail->url ?: null, ToolResult::OUR_DATA)]);
    }
}
