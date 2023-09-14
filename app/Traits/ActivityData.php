<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;

/**
 * Adventurousness = The uniqueness of the experience, and how outside an average person's comfort zone it is.
 * Solitariness = How much a person who enjoys less-crowded or non-human-interacting experiences would enjoy it.
 * Strenousness = How physically exerting the experience is.
 */
trait ActivityData
{
    private $activities;
    private string $activityType;
    private float $busyness = 0.5;

    public function __construct()
    {
        $this->activities = Activity::all()->keyBy('name');
    }

    private function activity($name): ?Activity
    {
        try {
            return $this->activities[$name];
        } catch (\Exception $e) {
            \Log::error('Activity ' . $name . ' not found.');
        }
        return null;
    }

    public function getActivityType(): string
    {
        return $this->activityType;
    }

    /**
     * Most places will have one activity type, but the same trail, for instance, could be used for either hiking or
     * off-roading. So we need a way to specify what we're using the parent class for.
     */
    public function setActivityType(string $activityType): void
    {
        $this->activityType = $activityType;
    }

    public function getBusyness(): float
    {
        return $this->busyness;
    }

    public function setBusyness(float $busyness): void
    {
        $this->busyness = $busyness;
    }

    public function adventurousness($activityType = null)
    {
        $adventurousness = null;
        switch (static::class) {
            case 'App\\Models\\Events':
                break;
            case 'App\\Models\\Location':
                break;
            case 'App\\Models\\Restaurant':
                $this->setActivityType('dining');
                $adventurousness = $this->calculateAdventurousnessForRestaurant();
                break;
            case 'App\\Models\\Trail':
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                $adventurousness = $this->calculateAdventurousnessForTrail();
            default:
        }
        return round($adventurousness, 2);
    }

    public function solitariness($activityType = null)
    {
        switch (static::class) {
            case 'App\\Models\\Events':
                break;
            case 'App\\Models\\Location':
                break;
            case 'App\\Models\\Restaurant':
                $this->setActivityType('dining');
                break;
            case 'App\\Models\\Trail':
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
            default:
        }
        $solitariness = $this->calculateSolitariness();
        return round($solitariness, 2);
    }

    public function strenuousness($activityType = null): float
    {
        $strenuousness = null;
        switch (static::class) {
            case 'App\\Models\\Trail':
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                $strenuousness = $this->calculateStrenuousnessForTrail();
                break;
            case 'App\\Models\\Events':
            case 'App\\Models\\Location':
            case 'App\\Models\\Restaurant':
            default:
                $strenuousness = $this->data->strenuousness;
        }
        return round($strenuousness, 2);
    }

    /**
     * The higher the average price, the more adventurous the restaurant (+/ 0.2).
     * 0.2 * tanh(0.1 * (x - 15))
     */
    private function calculateAdventurousnessForRestaurant(): float
    {
        $activityType = $this->getActivityType();
        return $this->activity($activityType)->adventurousness + 0.2 * tanh(0.1 * ($this->getAveragePrice() - 15));
    }

    /**
     * The more "unique" the trail, the higher the adventurousness.
     */
    private function calculateAdventurousnessForTrail(): float
    {
        $activityType = $this->getActivityType();
        return $this->activity($activityType)->adventurousness;
    }

    /**
     * The busier the event, restaurant, or trail, the lower the solitariness.
     * (x - 0.5) / y
     */
    private function calculateSolitariness(): float
    {
        $activityType = $this->getActivityType();
        $busynessAdj = ($this->getBusyness() - 0.5);

        switch (static::class) {
            // We don't care so much about solitariness at a restaurant.
            case 'App\\Models\\Restaurant':
                $busynessAdj = $busynessAdj / 3;
                break;
            case 'App\\Models\\Event':
            case 'App\\Models\\Location':
            case 'App\\Models\\Trail':
            default:
                $busynessAdj = $busynessAdj / 1.5;
        }
        return $this->activity($activityType)->solitariness - $busynessAdj;
    }

    /**
     * The incline/decline, distance, and grade are used for calculating the strenuousness of the trail (up to +0.5).
     * Account for differences between hiking and off-roading by calling setActivityType() before this function.
     */
    private function calculateStrenuousnessForTrail(): float
    {
        $activityType = $this->getActivityType();

        // Calculate the individual terms.
        $K = 0.75; // Incline/decline sensitivity
        $D = 1; // Distance sensitivity
        $G = 1.25; // Grade sensitivity
        $A = 3.0;  // Distance threshold

        $incline = (int)$this->incline_ft;
        $decline = (int)$this->decline_ft;
        $distance = (float)$this->distance_miles;
        $avgGrade = (float)$this->average_grade_pt;
        $maxGrade = (float)$this->max_grade_pt;

        // Use a logarithmic value based on distance, so extremely long distances don't skew the results too much. This
        // will have a wider curve for smaller numbers, and cap out at 7.5.
        $distance = 7.5 / (1 + exp(-$distance * 7.5));

        $inclineDeclineTerm = 1.0 / (1.0 + exp(-$K * ($incline - $decline)));
        $distanceTerm = 1.0 / (1.0 + exp($D * ($distance - $A)));
        $gradeTerm = 1.0 / (1.0 + exp(-$G * ($avgGrade - $maxGrade)));

        // Calculate the overall strenuousness score.
        $strenuousness = 0.5 * (1.0 - $inclineDeclineTerm) * (1.0 - $distanceTerm) * (1.0 - $gradeTerm);

        // Ensure the result is between 0 and 0.5.
        $strenuousness = max(0, min(0.5, $strenuousness));

        // Take into account what the difficulty field specifies.
        if ($this->isEasy()) {
            $strenuousness -= 0.2;
        }
        if ($this->isDifficult()) {
            $strenuousness += 0.2;
        }

        // Take into account the average hours of the trail (-0.1/+0.2).
        if ($this->average_hours) {
            $strenuousness += (0.4 * (1 - exp(-0.2 * $this->average_hours)) - 0.2) / 2;
        }

        return $this->activity($activityType)->adventurousness + $strenuousness;
    }
}
