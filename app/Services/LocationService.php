<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Location;
use App\Models\ResourceBusy;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\TravelRoute;
use App\Services\OCRService;
use App\Services\OpenAIService;
use Exception;
use Illuminate\Support\Facades\Log;
use Riverside\Osrm\Exception as OsrmException;
use Riverside\Osrm\Service\Route;
use Riverside\Osrm\Service\Trip;
use Symfony\Component\Process\Process;

class LocationService
{
    const DEFAULT_MPH_FOR_ESTIMATE = 30;
    const DEFAULT_MULTIPLIER_FOR_DRIVING_DISTANCE = 1.125;
    const KM_TO_MILES = 1.609344;

    private $route;

    public function __construct()
    {
        $this->route = new Route();
        $this->trip = new Trip();
    }

    private function getDrivingDistanceWithOSRM($fromLat, $fromLng, $toLat, $toLng)
    {
        // Check if we've already saved this distance.
        $travelRoute = TravelRoute::where([
            'lat_origin' => $fromLat,
            'lng_origin' => $fromLng,
            'lat_dest' => $toLat,
            'lng_dest' => $toLng,
        ])->first();
        if ($travelRoute) {
            return $travelRoute;
        }

        // Get the route details.
        $coordinates = $fromLng . ',' . $fromLat . ';' . $toLng . ',' . $toLat;
        try {
            $response = $this->route
                ->setSteps('true')
                ->fetch($coordinates);
            if ($response->isOK())
            {
                $totalDistance = 0;
                $totalDuration = 0;
                $data = $response->toArray();
                foreach ($data['routes'][0]['legs'] as $leg) {
                    foreach ($leg['steps'] as $step) {
                        $totalDistance += $step['distance'];
                        $totalDuration += $step['duration'];
                    }
                }

                $distanceMiles = round(($totalDistance / 1000) / self::KM_TO_MILES, 1);
                $durationHours = round($totalDuration / 3600, 1);

                $travelRoute = TravelRoute::create([
                    'lat_origin' => $fromLat,
                    'lng_origin' => $fromLng,
                    'lat_dest' => $toLat,
                    'lng_dest' => $toLng,
                    'distance_miles' => $distanceMiles,
                    'duration_hours' => $durationHours,
                ]);
                return $travelRoute;
            } else {
                echo 'Route not found.';
            }
        } catch (OsrmException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function getDrivingDistanceWithHaversine($fromLat, $fromLng, $toLat, $toLng): float
    {
        $distanceMiles = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        $distanceMiles *= self::DEFAULT_MULTIPLIER_FOR_DRIVING_DISTANCE;
        return round($distanceMiles, 1);
    }

    private function getDrivingHoursWithOSRM($fromLat, $fromLng, $toLat, $toLng): float
    {
        $osrm = $this->getDrivingDistanceWithOSRM($fromLat, $fromLng, $toLat, $toLng);
        $durationHours = $osrm->duration_hours;
        return round($durationHours, 1);
    }

    private function getDrivingHoursWithConstant($fromLat, $fromLng, $toLat, $toLng): float
    {
        $distanceMiles = $this->getDrivingDistanceWithHaversine($fromLat, $fromLng, $toLat, $toLng);
        return round($distanceMiles / self::DEFAULT_MPH_FOR_ESTIMATE, 1);
    }

    public function estimateDrivingDistance($fromLat, $fromLng, $toLat, $toLng): float
    {
        return $this->getDrivingDistanceWithOSRM($fromLat, $fromLng, $toLat, $toLng);
    }

    public function estimateDrivingHours($fromLat, $fromLng, $toLat, $toLng): float
    {
        return $this->getDrivingHoursWithOSRM($fromLat, $fromLng, $toLat, $toLng);
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
