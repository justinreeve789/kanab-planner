<?php

namespace App\Chat\Data;

use App\Models\Accommodation;
use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Services\Routing\Point;

/**
 * Where things are: turns tool arguments (a trail, place, restaurant, area, the traveler's lodging or raw
 * coordinates) into a point, and knows which time zone a point is in.
 */
final class Geo
{
    /** Utah and Arizona meet at 37°N here. Arizona (outside the Navajo Nation) stays on MST all year. */
    public static function timezoneFor(float $lat, float $lng): string
    {
        return $lat < 37.0 && $lng > -114.05 ? 'America/Phoenix' : 'America/Denver';
    }

    /**
     * @return array{point: Point, label: string, area_id: ?int}|null
     */
    public static function resolve(array $args, ?\App\Chat\ChatContext $context = null): ?array
    {
        if (!empty($args['trail_id']) && ($trail = Trail::find($args['trail_id']))) {
            $point = Point::make($trail->latitude, $trail->longitude) ?? self::trailStart($trail->id);
            return $point ? ['point' => $point, 'label' => $trail->name, 'area_id' => $trail->area_id] : null;
        }
        if (!empty($args['restaurant_id']) && ($restaurant = Restaurant::with('location')->find($args['restaurant_id'])) && $restaurant->location) {
            $point = Point::make($restaurant->location->latitude, $restaurant->location->longitude);
            return $point ? ['point' => $point, 'label' => $restaurant->name, 'area_id' => $restaurant->location->area_id] : null;
        }
        if (!empty($args['location_id']) && ($location = Location::find($args['location_id']))) {
            $point = Point::make($location->latitude, $location->longitude);
            return $point ? ['point' => $point, 'label' => $location->name, 'area_id' => $location->area_id] : null;
        }
        if (!empty($args['accommodation_id']) && ($place = Accommodation::with('location')->find($args['accommodation_id'])) && $place->location) {
            $point = Point::make($place->location->latitude, $place->location->longitude);
            return $point ? ['point' => $point, 'label' => $place->name, 'area_id' => $place->location->area_id] : null;
        }
        if (!empty($args['event_id']) && ($event = Event::find($args['event_id']))) {
            $point = Point::make($event->latitude, $event->longitude);
            return $point ? ['point' => $point, 'label' => $event->name, 'area_id' => null] : null;
        }
        if (!empty($args['lodging']) && $context?->itinerary) {
            $itinerary = $context->itinerary;
            $point = Point::make($itinerary->latitude, $itinerary->longitude);
            $base = $itinerary->getBaseArea();
            if (!$point && $base) {
                $point = Point::make($base->latitude, $base->longitude);
            }
            return $point ? ['point' => $point, 'label' => $itinerary->lodging_name ?: 'your lodging', 'area_id' => $base?->id] : null;
        }
        if (isset($args['lat'], $args['lng']) && ($point = Point::make($args['lat'], $args['lng']))) {
            return ['point' => $point, 'label' => $args['label'] ?? $point->key(), 'area_id' => Area::getAreaForLatLng($point->lat, $point->lng)?->id];
        }
        if (!empty($args['area_id']) && ($area = Area::find($args['area_id']))) {
            $point = Point::make($area->latitude, $area->longitude);
            return $point ? ['point' => $point, 'label' => $area->name, 'area_id' => $area->id] : null;
        }
        return null;
    }

    /** First point of a trail's GPS track, for trails without trailhead coordinates. */
    public static function trailStart(int $trailId): ?Point
    {
        $geojson = TrailGeoJson::where('trail_id', $trailId)->value('geojson');
        $points = $geojson ? TrailProfiler::points(json_decode($geojson, true)) : [];
        return $points ? new Point($points[0][0], $points[0][1]) : null;
    }

    /** JSON-schema properties tools share for "where": pick one. */
    public static function placeSchema(bool $withLodging = true): array
    {
        $properties = [
            'trail_id' => ['type' => 'integer'],
            'location_id' => ['type' => 'integer'],
            'restaurant_id' => ['type' => 'integer'],
            'accommodation_id' => ['type' => 'integer'],
            'event_id' => ['type' => 'integer'],
            'area_id' => ['type' => 'integer'],
            'lat' => ['type' => 'number'],
            'lng' => ['type' => 'number'],
        ];
        if ($withLodging) {
            $properties['lodging'] = ['type' => 'boolean', 'description' => 'The traveler\'s lodging from their itinerary.'];
        }
        return $properties;
    }
}
