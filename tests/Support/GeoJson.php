<?php

namespace Tests\Support;

class GeoJson
{
    /**
     * A straight LineString from (lat1, lng1) to (lat2, lng2) with $points evenly spaced vertices and elevation
     * interpolated linearly. Coordinates are [lng, lat, elevation] per the GeoJSON spec.
     */
    public static function line(float $lat1, float $lng1, float $lat2, float $lng2, int $points, float $elev1, float $elev2): string
    {
        $coordinates = [];
        for ($i = 0; $i <= $points; $i++) {
            $t = $i / $points;
            $coordinates[] = [
                $lng1 + ($lng2 - $lng1) * $t,
                $lat1 + ($lat2 - $lat1) * $t,
                $elev1 + ($elev2 - $elev1) * $t,
            ];
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
            ]],
        ]);
    }
}
