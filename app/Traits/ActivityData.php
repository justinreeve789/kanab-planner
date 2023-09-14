<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;

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

    public function solitude($activityType = null)
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
        $solitude = $this->calculateSolitude();
        return round($solitude, 2);
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
     * The more strenuous, the more adventurous the trail (up to +0.5).
     * Account for differences between hiking and off-roading by calling setActivityType() first.
     */
    private function calculateAdventurousnessForTrail(): float
    {
        $activityType = $this->getActivityType();

        // Calculate the individual terms
        $K = 0.01; // Incline/decline sensitivity
        $D = 0.01; // Distance sensitivity
        $G = 0.01; // Grade sensitivity
        $A = 5.0;  // Distance threshold

        $incline = (int)str_replace("'", "", $this->incline);
        $decline = (int)str_replace("'", "", $this->decline);
        $distance = (float)str_replace(' Miles', '', $this->distance_miles);
        $avgGrade = 0.06;
        $maxGrade = 0.17;

        $inclineDeclineTerm = 1.0 / (1.0 + exp(-$K * ($incline - $decline)));
        $distanceTerm = 1.0 / (1.0 + exp($D * ($distance - $A)));
        $gradeTerm = 1.0 / (1.0 + exp(-$G * ($avgGrade - $maxGrade)));

        // Calculate the overall strenuousness score
        $strenuousness = 0.5 * (1.0 - $inclineDeclineTerm) * (1.0 - $distanceTerm) * (1.0 - $gradeTerm);

        // Ensure the result is between 0 and 0.5
        $strenuousness = max(0, min(0.5, $strenuousness));
        return $this->activity($activityType)->adventurousness + $strenuousness;
    }

    /**
     * The busier the event, restaurant, or trail, the lower the solitude.
     * (x - 0.5) / y
     */
    private function calculateSolitude(): float
    {
        $activityType = $this->getActivityType();
        $busynessAdj = ($this->getBusyness() - 0.5);

        switch (static::class) {
            // We don't care so much about solitude at a restaurant.
            case 'App\\Models\\Restaurant':
                $busynessAdj = $busynessAdj / 3;
                break;
            case 'App\\Models\\Event':
            case 'App\\Models\\Location':
            case 'App\\Models\\Trail':
            default:
                $busynessAdj = $busynessAdj / 1.5;
        }
        return $this->activity($activityType)->solitude - $busynessAdj;
    }
}
