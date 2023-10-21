<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;

trait Proximity
{
    public function calculateDistance($lat, $lon) {
        // Radius of the Earth in miles (mean value).
        $earthRadius = 3959;

        // Convert latitude and longitude from degrees to radians.
        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($lat);
        $lon2 = deg2rad($lon);

        // Haversine formula.
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
