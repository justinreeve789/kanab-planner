<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Trail;
use Exception;

class TrailService
{
    const MAX_DECLINE_BICYCLING_SPEED = 15; // Maximum bicycling speed on a decline (miles per hour)
    const MAX_DECLINE_HIKING_SPEED = 3.7; // Maximum hiking speed on a decline (miles per hour)
    const MIN_INCLINE_BICYCLING_SPEED = 1; // Minimum bicycling speed on an incline (miles per hour)
    const MIN_INCLINE_HIKING_SPEED = 0.2; // Minimum hiking speed on an incline (miles per hour)

    /** Track points are merged into pieces at least this long before grades are taken, so GPS jitter averages out. */
    const RESAMPLE_MILES = 0.1;
    /** Each mile already covered slows you down by this fraction. */
    const FATIGUE_PER_MILE = 0.02;
    /**
     * How strongly grade slows hiking: Tobler's function uses 3.5; half that matches the published durations in
     * database/dumps far better (97% of trails vs 94%), as does TIME_MULTIPLIER for breaks and photo stops.
     * See tests/Data/TrailEstimatesTest.
     */
    const GRADE_FACTOR = 2.0;
    const TIME_MULTIPLIER = 1.25;
    const FEET_PER_METER = 3.28084;

    /**
     * A random trail in the area whose best time of day is $period, drawn from the ~20 trails closest to the user's
     * adventurousness/solitariness/strenuousness and within their distance range.
     */
    public function getRandomTrailForPeriod($areaId, $period, $itinerary = null, $existingTrails = []): ?Trail
    {
        $trails = Trail::query()
            ->where('trails.area_id', $areaId)
            ->whereNotNull('trails.distance_miles')
            ->whereNotIn('trails.id', $existingTrails);

        if ($period === Period::EVENING) {
            $trails->where('trails.distance_miles', '<=', 2.0);
        }
        if ($itinerary !== null) {
            // The minimum distance is about main hikes; an evening walk is meant to be short.
            if ($period !== Period::EVENING) {
                $trails->where('trails.distance_miles', '>=', $itinerary->calculateMinimumTrailDistance());
            }
            $trails->where('trails.distance_miles', '<=', $itinerary->calculateMaximumTrailDistance())
                ->sortByDeviation([
                    'adventurousness' => $itinerary->getAdventurousness(),
                    'solitariness' => $itinerary->getSolitariness(),
                    'strenuousness' => $itinerary->getStrenuousness(),
                ], [
                    'adventurousness' => 12,
                    'solitariness' => 7,
                    'strenuousness' => 25,
                ], ActivityType::idFor(ActivityType::HIKING))
                ->limit(20);
        } else {
            $trails->inRandomOrder()->limit(20);
        }

        $candidates = $trails->get()->shuffle();
        foreach ($candidates as $trail) {
            if ($this->getBestPeriodOfDayForTrail($trail) === $period) {
                return $trail;
            }
        }
        return null;
    }

    /**
     * When to start a trail:
     * - 6+ hours, or 2.5+ hours in Zion (crowds): morning.
     * - 3+ hours: morning or afternoon.
     * - Up to 1.5 hours and not difficult: evening (a sunset walk).
     * - Short but difficult: morning or afternoon.
     * - Anything else: morning.
     *
     * TODO: Use busyness tracking and forecast heat.
     */
    public function getBestPeriodOfDayForTrail($trail, float $modifier = 0)
    {
        if (is_integer($trail)) {
            $trail = Trail::where('id', $trail)->first();
        }
        $hours = (float) $trail->average_hours;

        if ($hours >= 6 || ($trail->area_id === Area::ZION && $hours >= 2.5)) {
            return Period::MORNING;
        }
        if ($hours >= 3) {
            return [Period::MORNING, Period::AFTERNOON][array_rand([0, 1])];
        }
        if ($hours > 0 && $hours <= 1.5) {
            return $trail->isDifficult() ? [Period::MORNING, Period::AFTERNOON][array_rand([0, 1])] : Period::EVENING;
        }
        return Period::MORNING;
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
     * Speed over one stretch of trail, in mph.
     *
     * Hiking follows the shape of Tobler's hiking function relative to flat ground: fastest on a slight descent
     * (−5%), slower in both directions from there. Bicycling slows ~10% per 1% of climb and gains a little on descents. Both are
     * capped by the MIN/MAX constants, then slowed by fatigue for the miles already covered.
     *
     * $grade is rise over run (0.1 = 10%).
     */
    public function segmentSpeed(float $averageSpeed, float $grade, float $milesSoFar, string $activityType = ActivityType::HIKING): float
    {
        if ($activityType === ActivityType::BICYCLING) {
            $speed = $grade >= 0 ? $averageSpeed / (1 + 10 * $grade) : $averageSpeed * min(1.5, 1 + 5 * abs($grade));
            $speed = max(self::MIN_INCLINE_BICYCLING_SPEED, min(self::MAX_DECLINE_BICYCLING_SPEED, $speed));
        } elseif ($activityType === ActivityType::HIKING) {
            $speed = $averageSpeed * exp(-self::GRADE_FACTOR * (abs($grade + 0.05) - 0.05));
            $speed = max(self::MIN_INCLINE_HIKING_SPEED, min(self::MAX_DECLINE_HIKING_SPEED, $speed));
        } else {
            throw new Exception('Valid activity type not specified.');
        }
        return $speed / (1 + self::FATIGUE_PER_MILE * $milesSoFar);
    }

    /**
     * Estimate how long a GPS track takes, and where you'll be at each hour.
     *
     * The track is resampled into pieces of at least RESAMPLE_MILES before grades are computed, so the estimate
     * depends on the terrain rather than on how densely the track was recorded. Elevations are meters (GPX).
     *
     * @return array{total_hours: float, time_hours: array<int, array{0: float, 1: float}>, total_dist: float, elevation_gain_ft: int}
     */
    public function analyzeTrail($geoJson, $averageSpeed, $activityType = ActivityType::HIKING, $roundPrecision = 1)
    {
        if (!in_array($activityType, [ActivityType::HIKING, ActivityType::BICYCLING], true)) {
            throw new Exception('Valid activity type not specified.');
        }
        $points = $this->extractTrailPoints($geoJson);
        if (count($points) < 2) {
            throw new \InvalidArgumentException('GeoJSON has no usable LineString coordinates.');
        }

        $averageSpeed = (float) $averageSpeed;
        $totalHours = 0.0;
        $totalMiles = 0.0;
        $gainMeters = 0.0;
        $hourly = [0 => [$points[0][1], $points[0][0]]];

        $anchor = $points[0];
        $pieceMiles = 0.0;
        $count = count($points);
        for ($i = 1; $i < $count; $i++) {
            $pieceMiles += $this->haversine($points[$i - 1][1], $points[$i - 1][0], $points[$i][1], $points[$i][0]);
            if ($pieceMiles < self::RESAMPLE_MILES && $i < $count - 1) {
                continue;
            }

            $rise = (($points[$i][2] ?? 0) - ($anchor[2] ?? 0));
            $grade = $pieceMiles > 0 ? $rise / ($pieceMiles * 1609.344) : 0.0;
            $gainMeters += max(0, $rise);

            $speed = $this->segmentSpeed($averageSpeed, $grade, $totalMiles, $activityType);
            $hours = $pieceMiles / $speed * self::TIME_MULTIPLIER;

            $before = (int) floor($totalHours);
            $totalHours += $hours;
            $totalMiles += $pieceMiles;
            for ($h = $before + 1; $h <= (int) floor($totalHours); $h++) {
                $hourly[$h] = [$points[$i][1], $points[$i][0]];
            }

            $anchor = $points[$i];
            $pieceMiles = 0.0;
        }

        return [
            'total_hours' => round($totalHours, $roundPrecision),
            'time_hours' => $hourly,
            'total_dist' => $totalMiles,
            'elevation_gain_ft' => (int) round($gainMeters * self::FEET_PER_METER),
        ];
    }
}
