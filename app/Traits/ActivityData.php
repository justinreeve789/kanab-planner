<?php

namespace App\Traits;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Location;
use App\Models\ResourceBusy;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;
use App\ResourceEnum;
use App\Services\DataService;
use Illuminate\Support\Facades\Log;

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

    private function shouldHaveAttribute($attr)
    {
        if (!isset($this->activity_type_id) || !isset($this->resource)) {
            return false;
        }
        switch (get_class($this->resource)) {
            case Event::class:
            case Location::class:
            case Restaurant::class:
            case Trail::class:
                return true;
            default:
                return false;
        }
    }


    public function getActivityTypeByName($name): ?ActivityType
    {
        if (!isset($this->activities)) {
            $this->activities = ActivityType::all()->keyBy('name');
        }
        try {
            return $this->activities[$name];
        } catch (\Exception $e) {
            Log::error('Activity ' . $name . ' not found.');
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
        $this->activityTypeId = $this->getActivityTypeByName($activityType)->id;
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
}
