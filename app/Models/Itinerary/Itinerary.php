<?php

namespace App\Models\Itinerary;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Models\User;
use App\Models\UserItinerary;
use App\ResourceEnum;
use App\Traits\Itinerary\Preferences;
use App\Traits\ReturnAllProperties;
use App\Traits\SnakeCaseAttributes;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Itinerary extends CachedModel
{
    use Preferences;

    const DEFAULT_ADVENTUROUSNESS = 0.5;
    const DEFAULT_SOLITARINESS = 0.5;
    const DEFAULT_STRENUOUSNESS = 0.5;
    const DEFAULT_THRIFTINESS = 0.5;
    const DEFAULT_CHECKIN_TIME = '16:00';
    const DEFAULT_CHECKOUT_TIME = '10:00';
    const DEFAULT_ACTIVITY_PRIORITIES = ['hiking'];
    const DEFAULT_ARRIVAL_TIME = '16:00';
    const DEFAULT_AVERAGE_HIKING_SPEED = 2;
    const DEFAULT_BASE_AREA_ID = Area::KANAB;
    const DEFAULT_DEPARTURE_TIME = '10:00';
    const DEFAULT_DURATION_DAYS = 5;
    const DEFAULT_PRIORITIES = ['trails', 'events', 'locations', 'restaurants'];
    const DEFAULT_SEASON = 'summer';
    const FALL = 'fall';
    const SPRING = 'spring';
    const SUMMER = 'summer';
    const WINTER = 'winter';

    protected $attributes = [
        'arrival_date_time' => '',
    ];
    protected $hidden = ['edit_token_hash'];
    protected $casts = [
        'activity_priorities' => 'json',
        'area_priorities' => 'json',
        'event_ids' => 'json',
        'event_priorities' => 'json',
        'interests' => 'json',
        'location_ids' => 'json',
        'location_priorities' => 'json',
        'priorities' => 'json',
        'restaurant_ids' => 'json',
        'restaurant_priorities' => 'json',
        'solitariness' => 'json',
        'strenuousness' => 'json',
        'trail_ids' => 'json',
        'trail_priorities' => 'json',
        'trip_preference' => 'json',
    ];

    public $base_area;

    /** Resources already scheduled while generating, so nothing is used twice. Not persisted. */
    public $event_ids;
    public $location_ids;
    public $restaurant_ids;
    public $trail_ids;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->id = Str::uuid();
        $this->adventurousness = $this->adventurousness ?? self::DEFAULT_ADVENTUROUSNESS;
        $this->average_hiking_speed = $this->average_hiking_speed ?? self::DEFAULT_AVERAGE_HIKING_SPEED;
        $this->base_area_id = $this->base_area_id ?? self::DEFAULT_BASE_AREA_ID;
        $this->duration_days = $this->duration_days ?? self::DEFAULT_DURATION_DAYS;
        $this->event_ids = [];
        $this->location_ids = [];
        $this->minimum_trail_days = 0;
        $this->restaurant_ids = [];
        $this->solitariness = $this->solitariness ?? self::DEFAULT_SOLITARINESS;
        $this->strenuousness = $this->strenuousness ?? self::DEFAULT_STRENUOUSNESS;
        $this->thriftiness = $this->thriftiness ?? self::DEFAULT_THRIFTINESS;
        $this->total_possible_trail_days = 0;
        $this->total_travel_distance = 0;
        $this->trail_ids = [];
    }

    public function getArrivalDateTimeAttribute()
    {
        if (isset($this->attributes['arrival_date_time'])) {
            return $this->attributes['arrival_date_time'];
        } else {
            if (isset($this->attributes['start_date'])) {
                $this->attributes['arrival_date_time'] = Carbon::parse($this->attributes['start_date'] . ' ' . self::DEFAULT_ARRIVAL_TIME)->format('Y-m-d H:i:s');
                return $this->attributes['arrival_date_time'];
            } else {
                throw new Exception('Could not set arrival date/time.');
            }
        }
    }

    public function days()
    {
        return $this->hasMany(Day::class, 'itinerary_id', 'id');
    }

    /**
     * Days, periods and activities in display order.
     */
    public function scopeWithSchedule($query)
    {
        return $query->with([
            'days' => fn($q) => $q->orderBy('day_num'),
            'days.periods' => fn($q) => $q->orderBy('type'),
            'days.periods.activities' => fn($q) => $q->orderBy('start_time'),
        ]);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_itineraries', 'itinerary_id', 'user_id')->withTimestamps();
    }

    /**
     * Issue a new edit token. Only its hash is stored, so the plain token can be returned exactly once.
     */
    public function issueEditToken(): string
    {
        $token = Str::random(40);
        $this->edit_token_hash = hash('sha256', $token);
        return $token;
    }

    public function hasEditToken(?string $token): bool
    {
        return $token !== null && $token !== '' && $this->edit_token_hash !== null
            && hash_equals($this->edit_token_hash, hash('sha256', $token));
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->users()->whereKey($user->id)->exists();
    }

    public function addTask($activityType, $name, $description, $startTime, $endTime, int $dayNum, $period = null): Activity
    {
        return $this->addActivity(null, $activityType, $startTime, $endTime, $dayNum, $period, $name, $description);
    }

    /**
     * Schedule something on day $dayNum. The period is derived from the start time (see Period::forTime()) unless
     * $period is given, so morning/afternoon/evening always agree with the clock.
     */
    public function addActivity($resource, $activityType, $startTime, $endTime, int $dayNum, $period = null, $name = null, $description = null, $phrase = null): Activity
    {
        $startTime = self::normalizeTime($startTime);
        $activity = new Activity([
            // Callers pass a mix of "H:i", "H:i:s" and "Y-m-d H:i:s"; the columns are TIME, so store H:i:s.
            'start_time' => $startTime,
            'end_time' => self::normalizeTime($endTime),
            'resource' => $resource,
            'activity_type_id' => ActivityType::idFor($activityType),
            'name' => $name,
            'description' => $description,
            'phrase' => $phrase,
        ]);
        $period = $period ?? Period::forTime($startTime);
        $this->getDay($dayNum)->getPeriod($period)->addActivity($activity);

        if ($resource) {
            if ($resource->isEvent()) {
                $this->event_ids[] = $resource->id;
            } else if ($resource->isLocation()) {
                $this->location_ids[] = $resource->id;
            } else if ($resource->isRestaurant()) {
                $this->restaurant_ids[] = $resource->id;
            } else if ($resource->isTrail()) {
                $this->trail_ids[] = $resource->id;
            }
        }
        return $activity;
    }

    public static function normalizeTime($time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }
        $timestamp = strtotime((string) $time);
        return $timestamp === false ? (string) $time : date('H:i:s', $timestamp);
    }

    public function calculateMaximumTrailDistance(): float
    {
        // A low adventurousness and strenuousness should result in generally shorter trails. Note that this isn't the
        // same as the maximum trail distance per day.
        if ($this->adventurousness <= 0.3 || $this->strenuousness <= 0.3) {
            return max(0.5, (($this->adventurousness * 0.75) + ($this->strenuousness * 1.25)) * 8);
        }
        return 9999.9;
    }

    public function calculateMinimumTrailDistance(): float
    {
        // A high adventurousness and strenuousness should result in generally longer trails. Note that this isn't the
        // same as the minimum trail distance per day.
        if ($this->adventurousness >= 0.7 || $this->strenuousness >= 0.6) {
            return 8 * (($this->adventurousness * 0.75) + ($this->strenuousness * 1.25)) / 5;
        }
        return 0.1;
    }

    /**
     * 0.0 is a legitimate slider value, so only fall back to the default when the preference is missing.
     */
    public function getAdventurousness(): ?float
    {
        if ($this->adventurousness === null || $this->adventurousness === '') {
            $this->adventurousness = self::DEFAULT_ADVENTUROUSNESS;
        }
        return $this->adventurousness;
    }

    public function getSolitariness(): ?float
    {
        if ($this->solitariness === null || $this->solitariness === '') {
            $this->solitariness = self::DEFAULT_SOLITARINESS;
        }
        return $this->solitariness;
    }

    public function getStrenuousness(): ?float
    {
        if ($this->strenuousness === null || $this->strenuousness === '') {
            $this->strenuousness = self::DEFAULT_STRENUOUSNESS;
        }
        return $this->strenuousness;
    }

    public function getBaseArea(): ?Area
    {
        if ($this->base_area === null && $this->base_area_id !== null) {
            $this->base_area = Area::where('id', $this->base_area_id)->first();
        }
        return $this->base_area;
    }

    public function getDay(int $dayNum = 0): Day
    {
        return $this->getDays()[$dayNum];
    }

    /**
     * Days keyed by day_num.
     *
     * @return Day[]
     */
    public function getDays(): array
    {
        if (!$this->relationLoaded('days')) {
            return [];
        }
        return $this->days->keyBy('day_num')->sortKeys()->all();
    }

    public function getArrivalDateTime()
    {
        return $this->arrival_date_time;
    }

    public function getDepartureDateTime()
    {
        if (isset($this->departure_date_time)) {
            return $this->departure_date_time;
        }
        $this->departure_date_time = date('Y-m-d H:i:s', strtotime($this->end_date . ' ' . self::DEFAULT_DEPARTURE_TIME));
        return $this->departure_date_time;
    }

    public function getTotalPossibleTrailDays(): ?int
    {
        return $this->total_possible_trail_days;
    }

    public function getTotalTravelDistance(): ?int
    {
        return $this->total_travel_distance;
    }

    public function eventExists($event): bool
    {
        return in_array($event->id, $this->event_ids);
    }

    public function locationExists($location): bool
    {
        return in_array($location->id, $this->location_ids);
    }

    public function restaurantExists($restaurant): bool
    {
        return in_array($restaurant->id, $this->restaurant_ids);
    }

    public function trailExists($trail): bool
    {
        return in_array($trail->id, $this->trail_ids);
    }

    public function getUseFirstDay(): bool
    {
        $a = strtotime($this->arrival_date_time);
        $b = strtotime($this->start_date . ' ' . self::DEFAULT_ARRIVAL_TIME);
        return $a < $b - 3600;
    }

    public function getUseLastDay(): bool
    {
        $a = strtotime($this->getDepartureDateTime());
        $b = strtotime($this->end_date . ' ' . self::DEFAULT_DEPARTURE_TIME);
        return $a > $b + 3600;
    }

    public function setBaseArea($area)
    {
        $this->base_area = $area;
    }

    /**
     * Held as the days relation (not an attribute), so save() never tries to write a "days" column and toArray()
     * still includes them.
     */
    public function setDays(array $days)
    {
        ksort($days);
        $this->setRelation('days', new \Illuminate\Database\Eloquent\Collection($days));
    }

    public function setTotalPossibleTrailDays(int $totalPossibleTrailDays)
    {
        $this->total_possible_trail_days = $totalPossibleTrailDays;
    }

    public function setTotalTravelDistance(int $totalTravelDistance)
    {
        $this->total_travel_distance = $totalTravelDistance;
    }
}
