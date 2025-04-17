<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\Image;
use App\Models\Itinerary\Activity;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Resource;
use App\Models\ResourceData;
use App\Models\ResourceMerge;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Scopes\DataScope;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Models\TrailImage;
use App\Models\TrailMerge;
use App\Repositories\ResourceRepository;
use App\ResourceEnum;
use App\Services\OpenAIService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DataService
{
    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;

    /**
     * The busier the event, restaurant, or trail, the lower the solitariness.
     * (x - 0.5) / y
     */
    public function solitariness($activityType = null)
    {
        $busynessAdj = $this->getBusyness();
        switch (static::class) {
            // We don't care so much about solitariness at a restaurant.
            case ResourceEnum::RESTAURANTS_CLASS:
                $busynessAdj = $busynessAdj / 3;
                $this->setActivityType('dining');
                break;
            case ResourceEnum::TRAILS_CLASS:
                $busynessAdj = $busynessAdj / 1.5;
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                break;
            case ResourceEnum::EVENTS_CLASS:
                if (isset($this->data[0])) {
                    $busynessAdj = -($this->data[0]->solitariness);
                }
                $this->setActivityType('event');
                break;
            case ResourceEnum::LOCATIONS_CLASS:
            default:
                $busynessAdj = $busynessAdj / 1.5;
        }
        $solitariness = ($this->getActivityTypeByName($activityType)->solitariness ?? 0.5) - $busynessAdj;
        return round($solitariness, 2);
    }

    public function strenuousness($activityType = null): float
    {
        switch (static::class) {
            case ResourceEnum::TRAILS_CLASS:
                if (!$this->getActivityType()) {
                    $this->setActivityType('hiking');
                }
                $strenuousness = $this->calculateStrenuousnessForTrail();
                break;
            case ResourceEnum::RESTAURANTS_CLASS:
                $strenuousness = 0;
                break;
            case ResourceEnum::EVENTS_CLASS:
                $strenuousness = reset($this->data)->strenuousness;
            case ResourceEnum::LOCATIONS_CLASS:
            default:
                $strenuousness = $this->data->strenuousness;
        }
        return round($strenuousness, 2);
    }

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
    public function calculateStrenuousnessForTrail($resource, $activityTypeId): float
    {
        // Calculate the individual terms.
        $K = 0.75; // Incline/decline sensitivity
        $D = 3; // Distance sensitivity
        $G = 1.25; // Grade sensitivity
        $A = 3.0;  // Distance threshold

        $incline = (int)$resource->incline_ft;
        $decline = (int)$resource->decline_ft;
        $distance = (float)$resource->distance_miles;
        $avgGrade = (float)$resource->average_grade_pt;
        $maxGrade = (float)$resource->max_grade_pt;

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
        if ($resource->isEasy()) {
            $strenuousness -= 0.2;
        }
        if ($resource->isDifficult()) {
            $strenuousness += 0.2;
        }

        // Take into account the average hours of the trail (-0.1/+0.2).
        if ($resource->average_hours) {
            $strenuousness += (0.4 * (1 - exp(-0.2 * $resource->average_hours)) - 0.2) / 2;
        }

        try {
            return ActivityType::find($activityTypeId)->strenuousness + $strenuousness;
        } catch (\Throwable $e) {
            Log::error('Could not calculate strenuousness for ' . $activityTypeId . ' ' . $resource->id . ' ' . $resource->name);
            return 0;
        }
    }
}
