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
    public const MORNING = 0;
    public const AFTERNOON = 1;
    public const EVENING = 2;
    public const NIGHT = 3;

    protected $casts = ['id' => 'string', 'day_id' => 'string'];
    protected $guarded = [];
    protected $table = 'itinerary_periods';

    /**
     * New periods start with an empty in-memory activity list; hydrated rows load theirs from the relation.
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        if (!empty($attributes)) {
            $this->id = $this->id ?? Str::uuid();
            $this->setRelation('activities', new Collection());
        }
    }

    /**
     * The column is ENUM('0','1','2','3'); MySQL treats a bare integer as an enum *index*, so always write the string.
     */
    public function setTypeAttribute($type): void
    {
        $this->attributes['type'] = (string) (int) $type;
    }

    public function getTypeAttribute($type): ?int
    {
        return $type === null ? null : (int) $type;
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'period_id', 'id');
    }

    public function getActivities(): Collection
    {
        return $this->activities->sortBy('start_time')->values();
    }

    public function addActivity(Activity $activity): void
    {
        $this->activities->add($activity);
    }

    /**
     * Which period a start time falls in: morning until 11:30, afternoon until 16:00, evening until 22:00.
     */
    public static function forTime(?string $time): int
    {
        $time = $time ? substr(Itinerary::normalizeTime($time), 0, 5) : '12:00';
        return match (true) {
            $time < '11:30' => self::MORNING,
            $time < '16:00' => self::AFTERNOON,
            $time < '22:00' => self::EVENING,
            default => self::NIGHT,
        };
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

    public static function getStartTimeForActivities(int $period): ?string
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

    public static function getTimesForMeals(int $period): ?array
    {
        switch ($period) {
            case Period::MORNING:
                return ['startTime' => '08:00:00', 'endTime' => '10:00:00'];
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
            if ($activity->activity_type?->name === $activityType) {
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
            if ($activity->resource && get_class($activity->resource) === $resourceClass) {
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
