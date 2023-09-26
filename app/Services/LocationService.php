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
    const DEFAULT_MULTIPLIER_FOR_DRIVING_DISTANCE = 1.5;

    public function __construct()
    {
    }

    public function estimateDrivingDistance($fromLat, $fromLng, $toLat, $toLng): float
    {
        $distanceMiles = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        $distanceMiles *= self::DEFAULT_MULTIPLIER_FOR_DRIVING_DISTANCE;
        return round($distanceMiles, 1);
    }

    public function estimateDrivingHours($fromLat, $fromLng, $toLat, $toLng): float
    {
        $distanceMiles = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        return round($distanceMiles / self::DEFAULT_MPH_FOR_ESTIMATE, 1);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $radiusOfEarth = 3959;
        $latitudeOne = deg2rad($lat1);
        $longitudeOne = deg2rad($lon1);
        $latitudeTwo = deg2rad($lat2);
        $longitudeTwo = deg2rad($lon2);

        $distanceLongitude = $longitudeTwo - $longitudeOne;
        $distanceLatitude = $latitudeTwo - $latitudeOne;
        $a = sin($distanceLatitude / 2) * sin($distanceLatitude/2) + cos($latitudeOne) * cos($latitudeTwo) * sin($distanceLongitude/2) * sin($distanceLongitude/2);
        $c = 2 * asin(sqrt($a));
        $distance = $radiusOfEarth * $c;
        return $distance;
    }
}