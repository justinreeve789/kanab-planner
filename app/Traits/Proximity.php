<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;

trait Proximity
{
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Radius of the Earth in kilometers (mean value).
        $earthRadius = 6371;

        // Convert latitude and longitude from degrees to radians.
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine formula.
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
