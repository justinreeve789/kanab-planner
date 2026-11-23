<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Location;
use App\Models\ResourceBusy;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\OCRService;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
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

    public function updateBusynessForLocation($location)
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
            Log::error(__CLASS__ . ': ' . $process->getErrorOutput());
            return null;
        }

        $currentDate = date('Y-m-d');

        foreach ($this->parsePopularTimes($process->getOutput()) as $dayOfWeek => [$day, $hours]) {
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

    /**
     * Parse populartimes.js output ("Day: Monday, Popular Times: [0, 0, 5, ...]") into
     * [day_of_week (0 = Sunday) => [day name, [hour => percentage]]].
     *
     * (Sunday used to be dropped: array_search() returns 0 for it, which the old truthiness check rejected. The real
     * dataset in database/dumps/resource_busy.sql has no Sunday rows at all because of this.)
     */
    public function parsePopularTimes(string $output): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        preg_match_all('/Day: (\w+), Popular Times: \[([\d\s,]+)\]/', $output, $matches, PREG_SET_ORDER);

        $parsed = [];
        foreach ($matches as $match) {
            $dayOfWeek = array_search($match[1], $days, true);
            $hours = array_map('intval', preg_split('/\s*,\s*/', trim($match[2]), -1, PREG_SPLIT_NO_EMPTY));
            if ($dayOfWeek !== false && !empty($hours)) {
                $parsed[$dayOfWeek] = [$match[1], $hours];
            }
        }
        return $parsed;
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
        $a = sin($distanceLatitude / 2) * sin($distanceLatitude / 2) + cos($latitudeOne) * cos($latitudeTwo) * sin($distanceLongitude / 2) * sin($distanceLongitude / 2);
        $c = 2 * asin(sqrt($a));
        $distance = $radiusOfEarth * $c;
        return $distance;
    }

    static public function search($params = []): ?Location {
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (!$value || trim($value) == '') {
                    return null;
                }
                if (!isset($loc)) {
                    $loc = Location::where($key, $value);
                }
                $loc = $loc->where($key, $value);
            }
            $location = $loc->first();
            if ($location) {
                return $location;
            }
        }
        return null;
    }
}
