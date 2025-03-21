<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Location;
use App\Models\ResourceBusy;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\OCRService;
use App\Services\OpenAIService;
use Symfony\Component\Process\Process;

class LocationService
{
    const DEFAULT_MPH_FOR_ESTIMATE = 30;
    const DEFAULT_MULTIPLIER_FOR_DRIVING_DISTANCE = 1.125;

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

    public function getBusynessForLocation($location)
    {
        if (is_numeric($location)) {
            $location = Location::where('id', $location)->first();
        }

        if ($location->source_type !== 'googleplaces') {
            return null;
        }

        $process = new Process(["python3", 'populartimes.js', $location->source_id]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->error('Error: ' . $process->getErrorOutput());
            return null;
        }

        $output = $process->getOutput();

        $pattern = '/Day: (\w+), Popular Times: \[([\d\s,]+)\]/';
        preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);
        $currentDate = date('Y-m-d');

        foreach ($matches as $match) {
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $day = $match[1];
            $hours = explode(', ', $match[2]);
            $dayOfWeek = array_search($day, $days);
            if ($dayOfWeek && !empty($hours)) {
                foreach ($hours as $hour => $percentage) {
                    ResourceBusy::updateOrCreate([
                        'resource_table' => 'locations',
                        'resource_id' => $location->id,
                        'day_of_week' => $dayOfWeek,
                        'hour' => $hour,
                        'collected_date' => $currentDate,
                    ], [
                        'day' => $day,
                        'percentage' => $percentage,
                    ]);
                }
            }
        }
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
