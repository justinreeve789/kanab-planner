<?php

namespace App\Models\Itinerary;

use App\ResourceEnum;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Period extends CachedModel
{
    public const MORNING = 'morning';
    public const AFTERNOON = 'afternoon';
    public const EVENING = 'evening';
    public const NIGHT = 'night';

    protected $casts = ['id' => 'string', 'day_id' => 'string'];
    protected $guarded = [];
    protected $table = 'itinerary_periods';

    public $activities;

    public function __construct(array $attributes = []) {
        $this->id = Str::uuid();
        $this->activities = new Collection();
        parent::__construct($attributes);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'period_id', 'id');
    }

    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): void
    {
        $this->activities->add($activity);
    }

    public function getAreaId(): int
    {
        return $this->area_id;
    }

    public static function getPeriodTypes()
    {
        return [
            Period::MORNING,
            Period::AFTERNOON,
            Period::EVENING,
            Period::NIGHT,
        ];
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

    public function getActivitiesOfType(string $activityType): Collection
    {
        $activities = new Collection();
        foreach ($this->activities as $activity) {
            if ($activity->activityType === $activityType) {
                $activities->add($activity);
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
        return $this->hasResourceClass(ResourceEnum::EVENTS_CLASS);
    }

    public function hasLocation(): bool
    {
        return $this->hasResourceClass(ResourceEnum::LOCATIONS_CLASS);
    }

    public function hasRestaurant(): bool
    {
        return $this->hasResourceClass(ResourceEnum::RESTAURANTS_CLASS);
    }

    public function hasTrail(): bool
    {
        return $this->hasResourceClass(ResourceEnum::TRAILS_CLASS);
    }
}
