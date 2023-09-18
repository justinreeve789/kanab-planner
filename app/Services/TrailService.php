<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Trail;

class TrailService
{
    public function __construct()
    {
    }

    /**
     * TODO: This doesn't seem to work properly.
     */
    public function getRandomTrailForPeriod($areaId, $period, $itinerary = null, $existingTrails = []): ?Trail
    {
        $trails = Trail::with('data')->where('area_id', $areaId);
        if ($itinerary !== null) {
            $trails = $trails->sortByDeviations($itinerary->getDeviations());
        }
        $trails = $trails->inRandomOrder()->limit(10)->get();
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
            default:
                $mph = 1.3;
        }
        return $mph;
    }

    public function calculateTrailDifficulty($trail): string
    {
        return 'Intermediate';
    }
}