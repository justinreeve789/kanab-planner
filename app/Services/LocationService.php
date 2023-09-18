<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\OCRService;
use App\Services\OpenAIService;

class LocationService
{
    const DEFAULT_MPH_FOR_ESTIMATE = 30;

    public function __construct()
    {
    }

    public function estimateDrivingHours($fromLat, $fromLng, $toLat, $toLng): float
    {
        $distanceMiles = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        return round($distanceMiles / self::DEFAULT_MPH_FOR_ESTIMATE, 1);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        // Radius of the Earth in miles.
        $earthRadius = 3958.8;

        // Convert latitude and longitude from degrees to radians.
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine formula.
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;
        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}