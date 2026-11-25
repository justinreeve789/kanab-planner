<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Models\TrailGeoJson;

class TrailDetails extends BaseTool
{
    public function name(): string
    {
        return 'trail_details';
    }

    public function description(): string
    {
        return 'The full record for one trail: distance and route type, listed climb, difficulty, typical hours, season, '
            . 'dogs, fees, permits or reservations, vehicle needed to reach the trailhead, accessibility notes, directions, '
            . 'description, coordinates and official link.';
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

        return ToolResult::ok(array_filter([
            'trail_id' => $trail->id,
            'name' => html_entity_decode($trail->name, ENT_QUOTES),
            'also_known_as' => $trail->name_alt,
            'area' => $trail->area?->name,
            'area_id' => $trail->area_id,
            'distance_miles' => $trail->distance_miles !== null ? (float) $trail->distance_miles : null,
            'route_type' => $trail->distance_type,
            'listed_climb_ft' => $trail->incline_ft,
            'listed_descent_ft' => $trail->decline_ft,
            'elevation_range_ft' => $trail->elevation_low_ft ? [$trail->elevation_low_ft, $trail->elevation_high_ft] : null,
            'difficulty_label' => $trail->difficulty,
            'typical_hours' => $trail->average_hours !== null ? (float) $trail->average_hours : null,
            'season' => $trail->season,
            'dogs' => $trail->pets_allowed,
            'fee' => $trail->fee === null ? null : (bool) $trail->fee,
            'permit_or_reservation' => $trail->reservation === null ? null : (bool) $trail->reservation,
            'vehicle_to_trailhead' => self::clip($trail->vehicle, 300),
            'accessibility' => self::clip($trail->accessibility, 300),
            'features' => $trail->features,
            'directions' => self::clip($trail->directions, 400),
            'description' => self::clip($trail->description, 900),
            'trailhead' => $trail->latitude ? ['lat' => (float) $trail->latitude, 'lng' => (float) $trail->longitude] : null,
            'official_url' => $trail->url,
            'contact' => $trail->contact_name ? trim($trail->contact_name . ' ' . ($trail->contact_url ?? '')) : null,
            'has_gps_profile' => TrailGeoJson::where('trail_id', $trail->id)->exists(),
            'record_source' => $trail->source_type,
        ], fn($v) => $v !== null && $v !== ''), [$this->trailSource($trail)]);
    }
}
