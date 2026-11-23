<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Trail;
use Exception;
use Illuminate\Support\Facades\Log;

class TrailService
{
    const DISTANCE_FATIGUE_ADJUSTER = 7;
    const INCLINE_FATIGUE_ADJUSTER = 500;
    const MAX_DECLINE_BICYCLING_SPEED = 15; // Maximum bicycling speed on a decline (miles per hour)
    const MAX_DECLINE_HIKING_SPEED = 3.7; // Maximum hiking speed on a decline (miles per hour)
    const MIN_INCLINE_BICYCLING_SPEED = 1; // Minimum bicycling speed on an incline (miles per hour)
    const MIN_INCLINE_HIKING_SPEED = 0.2; // Minimum hiking speed on an incline (miles per hour)
    const TIME_MULTIPLIER = 1;

    /**
     * TODO: This doesn't seem to work properly.
     */
    public function getRandomTrailForPeriod($areaId, $period, $itinerary = null, $existingTrails = []): ?Trail
    {
        $totalTrailsForArea = Trail::where('area_id', $areaId)->count();
        $trails = Trail::query();
        if ($itinerary !== null) {
            // Consider the closest-matching 10–20 trails (~1/9th of the area), then pick at random among them.
            $trails = $trails->sortByDeviation([
                'adventurousness' => $itinerary->getAdventurousness(),
                'solitariness' => $itinerary->getSolitariness(),
                'strenuousness' => $itinerary->getStrenuousness(),
            ], [
                'adventurousness' => 12,
                'solitariness' => 7,
                'strenuousness' => 25,
            ])->take((int) min(20, max(10, ceil($totalTrailsForArea / 9))))->inRandomOrder();
        } else {
            $trails->inRandomOrder();
        }
        $trails->where('area_id', $areaId)
            ->whereNotNull('distance_miles')
            ->whereNotIn('trails.id', $existingTrails);
        if ($period === Period::EVENING) {
            $trails->where('distance_miles', '<=', '2.0');
        }
        if ($itinerary !== null) {
            $trails->where('distance_miles', '>=', $itinerary->calculateMinimumTrailDistance());
            $trails->where('distance_miles', '<=', $itinerary->calculateMaximumTrailDistance());
        }

        $trails->with(['area', 'images']);
        $trails = $trails->get();
        foreach ($trails as $trail) {
            $p = $this->getBestPeriodOfDayForTrail($trail);
            if ($p == $period) {
                return $trail;
            }
        }
        return null;
    }

    /**
     * TODO: Add more log into this, using busyness tracking. For now, we'll just say to do it in the morning if it's
     * at Zion, due to the crowds. If it's a longer trail (6+ hours), we'll also say to do it in the morning. If it's
     * easy and short (less than 1 mile), evening. Otherwise, afternoon.
     */
    public function getBestPeriodOfDayForTrail($trail, float $modifier = 0)
    {
        $periods = [];

        if (is_integer($trail)) {
            $trail = Trail::where('id', $trail)->first();
        }

        if ($trail->average_hours >= 6) {
            return Period::MORNING;
        }

        if ($trail->area_id === Area::ZION && $trail->average_hours >= 2.5) {
            return Period::MORNING;
        }

        if ($trail->average_hours <= 1.5) {
            if ($trail->difficulty != 'Difficult') {
            } else {
                $periods = array_merge($periods, [Period::MORNING, Period::AFTERNOON]);
            }
        }

        if ($trail->average_hours >= 3) {
            $periods = array_merge($periods, [Period::MORNING, Period::AFTERNOON]);
        }

        if (empty($periods)) {
            return Period::MORNING;
        }

        return $periods[array_rand($periods)];
    }

    /**
     * Use the NPS formula to calculate hiking time. See https://adventurenerds.com/article/hiking-time-calculator-and-how-to-estimate-hiking-time/
     */
    public function calculateHikingTime($distanceMiles, float $mph = null, string $difficulty = null, float $modifier = 0): float
    {
        if ($mph === null) {
            $mph = $this->calculateMphByDifficulty($difficulty);
        }

        // Calculate hiking time (in hours) based on distance and difficulty.
        return $distanceMiles / ($mph + $modifier);
    }

    /**
     * The listed difficulty affects the total hiking time calculation, though we use additional means to arrive at the final estimate.
     */
    public function getMphMultiplierForDifficulty(?string $difficulty): float
    {
        return match ($difficulty) {
            'Easy' => 1.2,
            'Easy/Intermediate' => 1.1,
            'Intermediate' => 1,
            'Intermediate/Difficult' => 0.9,
            'Difficult' => 0.8,
            'Very Difficult' => 0.7,
            default => 1,
        };
    }

    public function calculateMphByDifficulty(?string $difficulty): float
    {
        switch ($difficulty) {
            case 'Easy':
                $mph = 1.5;
                break;
            case 'Easy/Intermediate':
                $mph = 1.4;
                break;
            case 'Intermediate':
                $mph = 1.3;
                break;
            case 'Intermediate/Difficult':
                $mph = 1.2;
                break;
            case 'Difficult':
                $mph = 1.1;
                break;
            case 'Very Difficult':
                $mph = 0.9;
                break;
            default:
                $mph = 1.3;
        }
        return $mph;
    }

    public function calculateTrailDifficulty($trail): string
    {
        return 'Intermediate';
    }

    /**
     * Using the user's hiking speed and the formula from the NPS, reference the geoJSON data to determine when users
     * will reach locations along the trail, in hour increments. If it's a short trail, use half-hour increments.
     * Account for weariness toward the end of the trail after an incline.
     *
     * TODO: Users should be able to report their own times after a hike so we can adjust our formula.
     *
     * @param $geoJson
     * @return array
     */
    public function estimateTimesAlongTrailPath(float $hikingSpeed, $geoJson): array
    {
    }

    public function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 3960; // Earth radius in miles (for miles calculation)

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in miles
    }

    /**
     * All [lng, lat, elevation?] points from the GeoJSON's LineString / MultiLineString features, in order.
     * (Previously only features[0] was read, so an empty FeatureCollection — trail 444 in the dumps — crashed.)
     */
    public function extractTrailPoints($geoJson): array
    {
        $data = is_array($geoJson) ? $geoJson : json_decode((string) $geoJson, true);
        if (!is_array($data)) {
            return [];
        }
        $features = match ($data['type'] ?? null) {
            'FeatureCollection' => $data['features'] ?? [],
            'Feature' => [$data],
            default => [['geometry' => $data]],
        };

        $points = [];
        foreach ($features as $feature) {
            $geometry = $feature['geometry'] ?? [];
            $lines = match ($geometry['type'] ?? null) {
                'LineString' => [$geometry['coordinates'] ?? []],
                'MultiLineString' => $geometry['coordinates'] ?? [],
                default => [],
            };
            foreach ($lines as $line) {
                foreach ($line as $point) {
                    if (is_array($point) && count($point) >= 2) {
                        $points[] = $point;
                    }
                }
            }
        }
        return $points;
    }

    /**
     * Calculate the time between two points, considering incline, decline, and average speed
     *
     * @param $dist
     * @param $elevationChange
     * @param $isIncline
     * @param $averageSpeed
     * @param $maxDeclineSpeed
     * @param $minInclineSpeed
     * @return float|int
     */
    public function calculateTime($dist, $elevationChange, $isIncline, $averageSpeed, $maxDeclineSpeed, $minInclineSpeed)
    {
        // Adjust speed based on incline (decrease for incline)
        if ($elevationChange > 0) {
            $averageSpeed -= 0.0003 * $elevationChange; // A simple penalty for each foot of incline
            $averageSpeed = max($averageSpeed, $minInclineSpeed); // Cap speed on steep inclines
            $isIncline = true;
        }

        // Adjust speed based on decline (increase but cap at max decline speed)
        elseif ($elevationChange < 0) {
            $averageSpeed += 0.0002 * abs($elevationChange); // A small boost for decline, but capped
            $averageSpeed = min($averageSpeed, $maxDeclineSpeed); // Cap speed on steep declines
        }

        // Account for fatigue (higher fatigue if there's significant elevation gain)
        if ($isIncline) {
            $fatiguePenalty = 1 + ($elevationChange / self::INCLINE_FATIGUE_ADJUSTER);
            $averageSpeed = $averageSpeed / $fatiguePenalty;
        }

        // Account for fatigue due to overall distance.
        $averageSpeed = $averageSpeed + (log(0.01 + $dist) / self::DISTANCE_FATIGUE_ADJUSTER);

        // Time = Distance / Speed (convert miles to hours)
        $time = $dist / $averageSpeed;

        return $time * self::TIME_MULTIPLIER;
    }

    /**
     * Parse the GeoJSON and estimate total time.
     *
     * @param $geoJson
     * @param float $averageSpeed Average speed (miles per hour)
     * @param $activityType
     * @return array
     */
    public function analyzeTrail($geoJson, $averageSpeed, $activityType = ActivityType::HIKING, $roundPrecision = 1)
    {
        $trailPoints = $this->extractTrailPoints($geoJson);
        if (count($trailPoints) < 2) {
            throw new \InvalidArgumentException('GeoJSON has no usable LineString coordinates.');
        }
        $totalTime = 0;
        $totalDist = 0;
        $cumulativeTime = 0;
        $hourlyCoordinates = [];
        $currentHour = 0;

        for ($i = 1; $i < count($trailPoints); $i++) {
            $point1 = $trailPoints[$i - 1];
            $point2 = $trailPoints[$i];

            $lat1 = $point1[1];
            $lon1 = $point1[0];
            $elev1 = isset($point1[2]) ? $point1[2] : 0;

            $lat2 = $point2[1];
            $lon2 = $point2[0];
            $elev2 = isset($point2[2]) ? $point2[2] : 0;

            // Calculate the distance between the two points in miles
            $dist = $this->haversine($lat1, $lon1, $lat2, $lon2);
            $totalDist += $dist;

            // Calculate the elevation change in feet
            $elevationChange = $elev2 - $elev1;

            // Calculate the time between the two points
            if ($activityType === ActivityType::BICYCLING) {
                $maxDeclineSpeed = self::MAX_DECLINE_BICYCLING_SPEED;
                $minInclineSpeed = self::MIN_INCLINE_BICYCLING_SPEED;
            } elseif ($activityType === ActivityType::HIKING) {
                $maxDeclineSpeed = self::MAX_DECLINE_HIKING_SPEED;
                $minInclineSpeed = self::MIN_INCLINE_HIKING_SPEED;
            } else {
                throw new Exception('Valid activity type not specified.');
            }

            $time = $this->calculateTime($dist, $elevationChange, $elevationChange > 0, $averageSpeed, $maxDeclineSpeed, $minInclineSpeed);

            // Add time for this segment
            $cumulativeTime += $time;
            $totalTime += $time;

            // Check if it's time to add coordinates for the current hour
            $newHour = floor($cumulativeTime);
            if ($newHour > $currentHour) {
                $hourlyCoordinates[$newHour] = [
                    [$lat2, $lon2]
                ];
                $currentHour = $newHour;
            } else {
                // Append coordinates if it's the same hour
                if (isset($hourlyCoordinates[$newHour])) {
                    $hourlyCoordinates[$newHour][] = [$lat2, $lon2];
                } else {
                    $hourlyCoordinates[$newHour] = [
                        [$lat2, $lon2]
                    ];
                }
            }
        }

        $hourLatLng = [];
        foreach ($hourlyCoordinates as $hour => $coordinates) {
            $hourLatLng[$hour] = $coordinates[0];
        }
        return [
            'total_hours' => round($totalTime, $roundPrecision),
            'time_hours' => $hourLatLng,
            'total_dist' => $totalDist,
        ];
    }
}
