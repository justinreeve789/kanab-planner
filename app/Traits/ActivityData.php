<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\ResourceBusy;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;

/**
 * Adventurousness = The uniqueness of the experience, and how outside an average person's comfort zone it is.
 * Solitariness = How much a person who enjoys less-crowded or non-human-interacting experiences would enjoy it.
 * Strenuousness = How physically exerting the experience is.
 */
trait ActivityData
{
    private $activities;
    private string $activityType;
    private float $busyness = 0.5;

    public function activity($name): ?Activity
    {
        if (!isset($this->activities)) {
            $this->activities = Activity::all()->keyBy('name');
        }
        try {
            return $this->activities[$name];
        } catch (\Exception $e) {
            \Log::error('Activity ' . $name . ' not found.');
        }
        return null;
    }

    public function getActivityType(): ?string
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
        $this->activityId = $this->activity($activityType);
    }

    public function getBusyness(): float
    {
        $averagePercentage = ResourceBusy::where('resource_table', $this->getTable())
            ->where('resource_id', $this->id)
            ->groupBy('resource_id')
            ->avg('percentage');
        if ($averagePercentage) {
            return round($averagePercentage / 100, 2);
        }
        return $this->busyness;
    }

    public function setBusyness(float $busyness): void
    {
        $this->busyness = $busyness;
    }

    public function adventurousness($activityType = null)
    {
        $adventurousness = null;
        $this->setActivityType($activityType);
        switch (static::class) {
            case 'App\\Models\\Events':
                $adventurousness = reset($this->data)->adventurousness;
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

    /**
     * The busier the event, restaurant, or trail, the lower the solitariness.
     * (x - 0.5) / y
     */
    public function solitariness($activityType = null)
    {
        $busynessAdj = $this->getBusyness();
        switch (static::class) {
            // We don't care so much about solitariness at a restaurant.
            case 'App\\Models\\Restaurant':
                $busynessAdj = $busynessAdj / 3;
                $this->setActivityType('dining');
                break;
            case 'App\\Models\\Trail':
                $busynessAdj = $busynessAdj / 1.5;
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                break;
            case 'App\\Models\\Event':
                if (isset($this->data[0])) {
                    $busynessAdj = -($this->data[0]->solitariness);
                }
                $this->setActivityType('event');
                break;
            case 'App\\Models\\Location':
            default:
                $busynessAdj = $busynessAdj / 1.5;
        }
        $solitariness = ($this->activity($activityType)->solitariness ?? 0.5) - $busynessAdj;
        return round($solitariness, 2);
    }

    public function strenuousness($activityType = null): float
    {
        switch (static::class) {
            case 'App\\Models\\Trail':
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                $strenuousness = $this->calculateStrenuousnessForTrail();
                break;
            case 'App\\Models\\Restaurant':
                $strenuousness = 0;
                break;
            case 'App\\Models\\Event':
                $strenuousness = reset($this->data)->strenuousness;
            case 'App\\Models\\Location':
            default:
                $strenuousness = $this->data->strenuousness;
        }
        return round($strenuousness, 2);
    }

    /**
     * The resource data for events is calculated in EventService and populated with "php artisan import:data --events."
     * TODO: Does this belong in the ResourceData model?
     */
    private function getResourceDataForEvent(): array
    {
    }

    private function getAdventurousnessForEvent(): float
    {
    }

    /**
     * The resource data for events is calculated in EventService and populated with "php artisan import:data --events."
     */
    private function getSolitarinessForEvent(): float
    {}

    /**
     * The resource data for events is calculated in EventService and populated with "php artisan import:data --events."
     */
    private function getStrenuousnessForEvent(): float
    {}

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
     * The more "unique" the trail, the higher the adventurousness (e.g. the Narrows has you wading through water,
     * Angels Landing involves a steep precarious ledge). The longer the duration of the trail, the higher the
     * adventurousness, since they'll be spending more time outdoors. The more remote the trail, the higher the
     * adventurousness.
     */
    private function calculateAdventurousnessForTrail(): float
    {
        $activityType = $this->getActivityType();
        $adventurousness = $this->activity($activityType)->adventurousness;
        if ($this->unique === 1) {
            $adventurousness = min(1.5 * $adventurousness, 1);
        }
        return $adventurousness;
    }

    /**
     * The incline/decline, distance, and grade are used for calculating the strenuousness of the trail (-0.2/+0.9).
     * Account for differences between hiking and off-roading by calling setActivityType() before this function.
     * Strenuousness = ActivityAdventurousness + K1 * K2 * K3

    Where:
    - ActivityAdventurousness is a constant representing the adventurousness of the trail based on the activity type.
    - K1 = 0.5 (a constant)
    - K2 = 1.0 - 1.0 / (1.0 + exp(-K * (incline - decline)))
    - K is the incline/decline sensitivity (0.75 in the code).
    - incline and decline are the incline and decline values of the trail.
    - K3 = 1.0 - 1.0 / (1.0 + exp(D * (distance - A)))
    - D is the distance sensitivity (1 in the code).
    - A is the distance threshold (3.0 in the code).

    - exp(x) is the exponential function: e^x, where e is the base of the natural logarithm.

    The result of K2 and K3 is constrained to the range [0, 1] using the sigmoid function (1.0 / (1.0 + exp(-x))). This ensures that these terms are between 0 and 1.

    Additionally, there are adjustments based on the difficulty and average hours of the trail:

    - If the trail is marked as easy, subtract 0.2 from the final score.
    - If the trail is marked as difficult, add 0.2 to the final score.
    - If there is an average hours value, adjust the final score based on it.

    This equation allows you to calculate the strenuousness score for a trail based on its characteristics and activity type. Simply plug in the values for incline, decline, distance, and other parameters to compute the score.
     */
    private function calculateStrenuousnessForTrail(): float
    {
        $activityType = $this->getActivityType();

        // Calculate the individual terms.
        $K = 0.75; // Incline/decline sensitivity
        $D = 3; // Distance sensitivity
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

        // Take into account what the difficulty field specifies (-0.2/+0.2).
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

        try {
            return $this->activity($activityType)->strenuousness + $strenuousness;
        } catch (\Throwable $e) {
            echo 'Could not calculate strenuousness for ' . $activityType . ' ' . $this->id . ' ' . $this->name . '
';
            return 0;
        }
    }
}
