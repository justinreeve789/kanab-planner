<?php

namespace App\Services;

use App\Models\ActivityType;
use App\ResourceEnum;

class DataService
{
    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;

    /**
     * TODO: Cache the results.
     */
    public function calculateAdventurousness($resource, $activityTypeId)
    {
        $adventurousness = null;
        switch (get_class($resource)) {
            case ResourceEnum::EVENTS_CLASS:
                $adventurousness = $this->calculateAdventurousnessForEvent($resource, $activityTypeId);
                break;
            case ResourceEnum::LOCATIONS_CLASS:
                $adventurousness = $this->calculateAdventurousnessForLocation($resource, $activityTypeId);
                break;
            case ResourceEnum::RESTAURANTS_CLASS:
                $adventurousness = $this->calculateAdventurousnessForRestaurant($resource, $activityTypeId);
                break;
            case ResourceEnum::TRAILS_CLASS:
                $adventurousness = $this->calculateAdventurousnessForTrail($resource, $activityTypeId);
                break;
            default:
        }
        return round($adventurousness, 2);
    }

    /**
     * TODO: Cache the results.
     */
    public function calculateSolitariness($resource, $activityTypeId)
    {
        $solitariness = null;
        switch (get_class($resource)) {
            case ResourceEnum::EVENTS_CLASS:
                $solitariness = $this->calculateSolitarinessForEvent($resource, $activityTypeId);
                break;
            case ResourceEnum::LOCATIONS_CLASS:
                $solitariness = $this->calculateSolitarinessForLocation($resource, $activityTypeId);
                break;
            case ResourceEnum::RESTAURANTS_CLASS:
                $solitariness = $this->calculateSolitarinessForRestaurant($resource, $activityTypeId);
                break;
            case ResourceEnum::TRAILS_CLASS:
                $solitariness = $this->calculateSolitarinessForTrail($resource, $activityTypeId);
                break;
            default:
        }
        return round($solitariness, 2);
    }

    /**
     * TODO: Cache the results.
     */
    public function calculateStrenuousness($resource, $activityTypeId)
    {
        $strenuousness = null;
        switch (get_class($resource)) {
            case ResourceEnum::EVENTS_CLASS:
                $strenuousness = $this->calculateStrenuousnessForEvent($resource, $activityTypeId);
                break;
            case ResourceEnum::LOCATIONS_CLASS:
                $strenuousness = $this->calculateStrenuousnessForLocation($resource, $activityTypeId);
                break;
            case ResourceEnum::RESTAURANTS_CLASS:
                $strenuousness = $this->calculateStrenuousnessForRestaurant($resource, $activityTypeId);
                break;
            case ResourceEnum::TRAILS_CLASS:
                $strenuousness = $this->calculateStrenuousnessForTrail($resource, $activityTypeId);
                break;
            default:
        }
        return round($strenuousness, 2);
    }

    private function calculateAdventurousnessForEvent($resource, $activityTypeId): float
    {
        return ActivityType::find($activityTypeId)->adventurousness;
    }

    private function calculateAdventurousnessForLocation($resource, $activityTypeId): float
    {
        return ActivityType::find($activityTypeId)->adventurousness;
    }

    /**
     * The higher the average price, the more adventurous the restaurant (+/ 0.2).
     * 0.2 * tanh(0.1 * (x - 15))
     */
    public function calculateAdventurousnessForRestaurant($resource, $activityTypeId): float
    {
        return ActivityType::find($activityTypeId)->adventurousness + 0.2 * tanh(0.1 * ($resource->getAveragePrice() - 15));
    }

    /**
     * The more "unique" the trail, the higher the adventurousness (e.g. the Narrows has you wading through water,
     * Angels Landing involves a steep precarious ledge). The longer the duration of the trail, the higher the
     * adventurousness, since they'll be spending more time outdoors. The more remote the trail, the higher the
     * adventurousness.
     */
    public function calculateAdventurousnessForTrail($resource, $activityTypeId): float
    {
        $adventurousness = ActivityType::find($activityTypeId)->adventurousness;

        if ($resource->unique === 1) {
            $adventurousness = min(1.5 * $adventurousness, 1);
        }

        if ($resource->distance_miles && $resource->distance_miles >= 5) {
            // This part controls how quickly the curve approaches 1.5
            $scale = 0.2;

            // Shift distance so 5 miles becomes the new 0
            $adjusted = log($resource->distance_miles - 4) * $scale + 1;
            $adventurousness = min($adjusted * $adventurousness, 1);
        }

        // TODO: Add calculation for remoteness.

        return $adventurousness;
    }

    public function calculateSolitarinessForEvent($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->solitariness;
    }

    public function calculateSolitarinessForLocation($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->solitariness;
    }

    public function calculateSolitarinessForRestaurant($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->solitariness;
    }

    /**
     * TODO: Use a busyness calculation for the trail to determine a solitariness modifier.
     */
    public function calculateSolitarinessForTrail($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->solitariness;
    }

    public function calculateStrenuousnessForEvent($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->strenuousness;
    }

    public function calculateStrenuousnessForLocation($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->strenuousness;
    }

    public function calculateStrenuousnessForRestaurant($resource, $activityTypeId)
    {
        return ActivityType::find($activityTypeId)->strenuousness;
    }

    public const DIFFICULTY_STRENUOUSNESS = [
        'Easy' => -0.10,
        'Easy/Intermediate' => -0.05,
        'Intermediate' => 0.0,
        'Intermediate/Difficult' => 0.05,
        'Difficult' => 0.10,
        'Very Difficult' => 0.15,
    ];

    /**
     * Strenuousness of a trail, 0–1, from how far, how much climbing, how steep and how long:
     *
     *   0.35 · (1 − e^(−miles/6)) + 0.35 · (1 − e^(−climb_ft/1500)) + 0.10 · min(1, avg_grade/15%)
     *   + 0.20 · (1 − e^(−hours/4)) + difficulty adjustment (−0.10 … +0.15)
     *
     * Every term grows with its input and saturates gently, so a longer or steeper trail always scores at least as
     * high. (The previous formula fed raw feet into steep sigmoids; any trail that climbed more than it descended
     * scored 0 on that term.) Trails with none of these fields get their activity type's default.
     */
    public function calculateStrenuousnessForTrail($resource, $activityTypeId): float
    {
        $miles = (float) $resource->distance_miles;
        $climb = (float) $resource->incline_ft;
        $grade = (float) $resource->average_grade_pt;
        $hours = (float) $resource->average_hours;

        if ($miles <= 0 && $climb <= 0 && $hours <= 0) {
            return (float) (ActivityType::find($activityTypeId)?->strenuousness ?? 0.5);
        }

        $score = 0.35 * (1 - exp(-$miles / 6))
            + 0.35 * (1 - exp(-max(0, $climb) / 1500))
            + 0.10 * min(1, max(0, $grade) / 0.15)
            + 0.20 * (1 - exp(-$hours / 4))
            + (self::DIFFICULTY_STRENUOUSNESS[$resource->difficulty] ?? 0.0);

        return max(0.0, min(1.0, $score));
    }
}
