<?php

namespace App\Models\Itinerary;

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
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Itinerary extends CachedModel
{
    use Preferences;
//    use ReturnAllProperties;
//    use SnakeCaseAttributes;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;
    const DEFAULT_THRIFTINESS = 0.5;
    const DEFAULT_CHECKIN_TIME = '16:00';
    const DEFAULT_CHECKOUT_TIME = '10:00';
    const DEFAULT_ACTIVITY_PRIORITIES = ['hiking'];
    const DEFAULT_ARRIVAL_TIME = '16:00';
    const DEFAULT_AVERAGE_HIKING_SPEED = 1.3;
    const DEFAULT_BASE_AREA_ID = Area::KANAB;
    const DEFAULT_DEPARTURE_TIME = '10:00';
    const DEFAULT_DURATION_DAYS = 5;
    const DEFAULT_PRIORITIES = ['trails', 'events', 'locations', 'restaurants'];
    const DEFAULT_SEASON = 'summer';
    const DEFAULT_TRAIL_PRIORITIES = [];
    const FALL = 'fall';
    const SPRING = 'spring';
    const SUMMER = 'summer';
    const WINTER = 'winter';

    protected $attributes = [
        'arrival_date_time' => '',
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
//    public $days;
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
        $this->base_area_id = $this->base_area_id ?? self::DEFAULT_BASE_AREA_ID;
        $this->duration_days = $this->duration_days ?? self::DEFAULT_DURATION_DAYS;
        $this->event_ids = [];
        $this->location_ids = [];
        $this->restaurant_ids = [];
        $this->solitariness = $this->solitariness ?? self::DEFAULT_SOLITARINESS;
        $this->strenuousness = $this->strenuousness ?? self::DEFAULT_STRENUOUSNESS;
        $this->thriftiness = $this->thriftiness ?? self::DEFAULT_THRIFTINESS;
        $this->total_possible_trails = 0;
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
                $this->attributes['arrival_date_time'] = date('Y-m-h H:i:s', strtotime($this->attributes['start_date'] . ' ' . self::DEFAULT_ARRIVAL_TIME));
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

    public function addActivity($resource, $activityType, $startTime, $endTime, int $dayNum, $period)
    {
        $activity = new Activity([
            'resource' => $resource,
            'activity_type' => $activityType,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        $this->days[$dayNum]->periods[$period]->addActivity($activity);

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

    public function deleteDay($index): array
    {
        if (isset($this->days[$index])) {
            unset($this->days[$index]);
        }
        return $this->days;
    }

    public function getActivities(): ?array
    {
        return $this->activities;
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

    public function getTotalPossibleTrails(): ?int
    {
        return $this->total_possible_trails;
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

    public function setTotalPossibleTrails(int $totalPossibleTrails)
    {
        $this->total_possible_trails = $totalPossibleTrails;
    }

    public function setTotalTravelDistance(int $totalTravelDistance)
    {
        $this->total_travel_distance = $totalTravelDistance;
    }
}
