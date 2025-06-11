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
    const DEFAULT_DAY_END_TIME = '21:00';
    const DEFAULT_DAY_START_TIME = '07:00';
    const DEFAULT_DEPARTURE_TIME = '10:00';
    const DEFAULT_DURATION_DAYS = 5;
    const DEFAULT_DINNER_TIME = '17:00';
    const DEFAULT_EARLIEST_DAY_TIME = '07:00';
    const DEFAULT_LUNCH_TIME = '12:00';
    const DEFAULT_PRIORITIES = ['trails', 'events', 'locations', 'restaurants'];
    const DEFAULT_SEASON = 'summer';
    const DEFAULT_TRAIL_PRIORITIES = [];
    const FALL = 'fall';
    const SPRING = 'spring';
    const SUMMER = 'summer';
    const WINTER = 'winter';

    protected $attributes = [
        'arrival_date_time' => '',
        'earliest_day_time' => self::DEFAULT_EARLIEST_DAY_TIME,
    ];
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

    public $activities;
    public $base_area;
    public $event_ids;
    public $event_priorities;
    public $location_ids;
    public $location_priorities;
    public $restaurant_ids;
    public $restaurant_priorities;
    public $trail_ids;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->id = Str::uuid();
        $this->activities = [];
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

    /**
     * We're using this as a singleton, so we can reference it from both ItineraryController and ItineraryService.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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

    public function user()
    {
        return $this->hasOneThrough(User::class, UserItinerary::class, 'user_id', 'id', 'id', 'itinerary_id');
    }

//    public function save(array $options = [])
//    {
//        return \DB::transaction(function () use ($options) {
//            $this->load('days');
//            parent::save($options);
//            if ($this->relationLoaded('days')) {
//                $this->days()->saveMany($this->getRelation('days'));
//            }
//        });
//    }
//
    public function saveAll()
    {
        $this->days->each()->saveAll();
    }

    public function addTask($activityType, $name, $description, $startTime, $endTime, int $dayNum, $period, $distanceMiles = null, $notes = null)
    {
        $this->addActivity(null, $activityType, $startTime, $endTime, $dayNum, $period, $name, $description, $distanceMiles, $notes);
    }

    public function addActivity($resource, $activityType, $startTime, $endTime, int $dayNum, $period, $name = null, $description = null, $distanceMiles = null, $notes = null)
    {
        $activity = new Activity([
            'resource' => $resource,
            'activity_type_id' => ActivityType::where('name', $activityType)->pluck('id')->first(),
            'name' => $name,
            'description' => $description,
            'distance_miles' => $distanceMiles,
            'notes' => $notes,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        $this->days[$dayNum]->periods[$period]->addActivity($activity);

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
    }

    public function removeActivity($resource, $activityType, int $dayNum, $period = null)
    {
        foreach ($this->days as &$day) {
            if ($day->day_num === $dayNum) {
                foreach ($day->periods as &$period) {
                    foreach ($period->activities as &$activity) {
                        if (isset($activity->resource)) {
                            if ($oldResource && $activity->resource->table === $oldResource->table && $activity->resource->id === $oldResource->id) {
                                // Old resource found. We'll replace it with the new one.
                                $itinerary->addActivity();
                                $activity = new Activity([
                                    'resource' => $newResource,
                                    'activity_type_id' => ActivityType::where('name', $activityType)->pluck('id')->first(),
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    public function addDay(Day $day, $index = null): array
    {
        if ($index !== null) {
            $this->days = array_merge(
                array_slice($this->days, 0, $index, true),
                [$day],
                array_slice($this->days, $index, count($this->days) - 1, true)
            );
        } else {
            $this->days[] = $day;
        }
        return $this->days;
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

    public function calculateMaximumTotalTrailDistancePerDay(): float
    {
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

    public function calculateMinimumTotalTrailDistancePerDay(): float
    {
        // A high adventurousness and strenuousness should result in generally longer trails. Note that this isn't the
        // same as the minimum trail distance per day.
        if ($this->adventurousness >= 0.6 && $this->strenuousness >= 0.6) {
            return 8 * (($this->adventurousness * 0.75) + ($this->strenuousness * 1.25)) / 2;
        }
        return 0.1;
    }

    public function deleteDay($index): array
    {
        if (isset($this->days[$index])) {
            unset($this->days[$index]);
        }
        return $this->days;
    }

    public function getActivitiesInArea(int $areaId)
    {
        $activitiesByArea = [];
        foreach ($this->days as $day) {
            if ($day->area_id === $areaId) {
                $activities = $day->getActivities();
                foreach ($activities as $activity) {
                    if ($activity->resource) {
                        $activitiesByArea[$activity->resource->type][$activity->resource->id] = (object) ['id' => $activity->resource->id];
                    }
                }
            }
        }
        return $activitiesByArea;
    }

    public function getActivities(): array
    {
        $activitiesByType = [];
        foreach ($this->days as $day) {
            $activities = $day->getActivities();
            foreach ($activities as $activity) {
                if ($activity->resource) {
                    $activitiesByType[$activity->resource->type][$activity->resource->id] = (object) [
                        'resource_id' => $activity->resource->id,
                        'area_id' => $activity->resource->area_id,
                    ];
                }
            }
        }
        return $activitiesByType;
    }

    public function getAdventurousness(): ?float
    {
        if (!$this->adventurousness) {
            $this->adventurousness = self::DEFAULT_ADVENTUROUSNESS;
        }
        return $this->adventurousness;
    }

    public function getSolitariness(): ?float
    {
        if (!$this->solitariness) {
            $this->solitariness = self::DEFAULT_SOLITARINESS;
        }
        return $this->solitariness;
    }

    public function getStrenuousness(): ?float
    {
        if (!$this->strenuousness) {
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

    public function getDay(int $index = 0): Day
    {
        return $this->days[$index];
    }

    /**
     * @return Day[]
     */
    public function getDays(): array
    {
        return $this->days;
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

    public function hasMeal($date, $period): bool
    {
        switch ($period) {
            case Period::MORNING:
                $startTime = '07:00:00';
                $endTime = '11:29:59';
                break;
            case Period::AFTERNOON:
                $startTime = '11:30:00';
                $endTime = '15:59:59';
                break;
            case Period::EVENING:
                $startTime = '16:00:00';
                $endTime = '23:59:59';
                break;
            default:
                return false;
        }

        foreach ($this->getActivities() as $activity) {
        }

        return false;
    }

    public function hasBreakfast(): bool
    {
        return $this->hasMeal(Period::MORNING);
    }

    public function hasDinner(): bool
    {
        return $this->hasMeal(Period::EVENING);
    }

    public function hasLunch(): bool
    {
        return $this->hasMeal(Period::AFTERNOON);
    }

    public function resourceExists($resource): bool
    {
        if ($this->getActivities()) {
            foreach ($this->getActivities() as $activity) {
                if (get_class($activity->data) === get_class($resource)) {
                    if ($activity->id === $resource->id) {
                        return true;
                    }
                }
            }
        }
        return false;
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

    public function setDays(array $days)
    {
        $this->days = $days;
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
