<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;

class ShowMap extends BaseTool
{
    public function name(): string
    {
        return 'show_map';
    }

    public function description(): string
    {
        return 'Show a "view map" button for a trail under your reply, when the traveler asks to see a trail on a map '
            . 'or where it is. Say one sentence about where it is too.';
    }

    public function parameters(): array
    {
        return self::object(['trail_id' => ['type' => 'integer']], ['trail_id']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['trail_id' => 'required|integer']);
        if (!($trail = $this->trail($args['trail_id']))) {
            return $this->notFound('trail', $args['trail_id']);
        }
        $place = Geo::resolve(['trail_id' => $trail->id]);
        if (!$place) {
            return ToolResult::unavailable('This trail has no coordinates, so there is no map to show.');
        }
        return new ToolResult(
            ['shown' => true, 'trail' => html_entity_decode($trail->name, ENT_QUOTES), 'area' => $trail->area?->name],
            [$this->trailSource($trail)],
            'buttons/ViewMap',
            ['resource' => ['id' => $trail->id, 'latitude' => $place['point']->lat, 'longitude' => $place['point']->lng]],
        );
    }
}
