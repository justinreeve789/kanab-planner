<?php

namespace App\Models\Itinerary;

use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    public const MORNING = 'morning';
    public const AFTERNOON = 'afternoon';
    public const EVENING = 'evening';
    public const NIGHT = 'night';

    protected $guarded = [];

    public $activities = [];
    public $areaId;
    public $type;

    public function __construct(?string $type, ?int $areaId) {
        if ($type !== null) {
            $this->setType($type);
        }
        if ($areaId !== null) {
            $this->setAreaId($areaId);
        }
    }

    public function addActivity($resource, $activityType = null, $startTime = null, $endTime = null)
    {
        $this->activities[] = new Activity($resource, $activityType, $startTime, $endTime);
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public static function getStartTimeForActivities(string $period): ?string
    {
        switch ($period) {
            case Period::MORNING:
                return '09:00:00';
            case Period::AFTERNOON:
                return '12:00:00';
            case Period::EVENING:
                return '17:00:00';
            case Period::NIGHT:
                return '22:00:00';
            default:
                return null;
        }
    }

    public static function getTimesForMeals(string $period): ?array
    {
        switch ($period) {
            case Period::MORNING:
                return ['startTime' => '09:00:00', 'endTime' => '10:00:00'];
            case Period::AFTERNOON:
                return ['startTime' => '12:00:00', 'endTime' => '13:30:00'];
            case Period::EVENING:
                return ['startTime' => '17:00:00', 'endTime' => '18:30:00'];
            default:
                return null;
        }
    }

    public function getActivitiesOfType(string $activityType): array
    {
        $activities = [];
        foreach ($this->activities as $activity) {
            if ($activity->activityType === $activityType) {
                $activities[] = $activity;
            }
        }
        return $activities;
    }

    public function hasActivityType(string $activityType): bool
    {
        $activities = $this->getActivitiesOfType($activityType);
        return count($activities) > 0;
    }

    public function hasResourceClass($resourceClass): bool
    {
        foreach ($this->activities as $activity) {
            if ($activity->data && get_class($activity->data) === $resourceClass) {
                return true;
            }
        }
        return false;
    }

    public function hasEvent(): bool
    {
        return $this->hasResourceClass('App\\Models\\Event');
    }

    public function hasLocation(): bool
    {
        return $this->hasResourceClass('App\\Models\\Location');
    }

    public function hasRestaurant(): bool
    {
        return $this->hasResourceClass('App\\Models\\Restaurant');
    }

    public function hasTrail(): bool
    {
        return $this->hasResourceClass('App\\Models\\Trail');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setAreaId(int $areaId) {
        $this->areaId = $areaId;
    }

    public function setType(string $type) {
        $this->type = $type;
    }
}
