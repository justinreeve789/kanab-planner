<?php

namespace App\Models\Itinerary;

use App\Models\Area;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Traits\Itinerary\Preferences;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    use HasFactory;
    use Preferences;
    use ReturnAllProperties;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;
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
    const DEFAULT_TRAIL_PRIORITIES = [216];
    const FALL = 'fall';
    const SPRING = 'spring';
    const SUMMER = 'summer';
    const WINTER = 'winter';

    protected $fillable = ['*'];

    public $accommodationPreference;
    public $activities;
    public $activityPriorities;
    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    public $arrivalDateTime;
    public $baseAreaId = self::DEFAULT_BASE_AREA_ID;
    public $checkInTime;
    public $checkOutTime;
    public $days;
    public $departureDateTime;
    public $durationDays;
    public $endDate;
    public $estimatedCost;
    public $eventIds = [];
    public $homeLocationId;
    public $interests;
    public $latitude;  // The latitude of the "base camp."
    public $locationIds = [];
    public $longitude; // The longitude of the "base camp."
    public $month;
    public $priorities;
    public $restaurantIds = [];
    public $season;
    public $solitariness = self::DEFAULT_SOLITARINESS;
    public $startDate;
    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    public $trailIds = [];
    public $trailPriorities;
    public $tripPreference;
    public $useFirstDay;
    public $useLastDay;

    public function __construct()
    {
        $this->days = [];
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

    public function days()
    {
        return $this->hasMany(Day::class, 'itinerary_id', 'id');
    }

    public function addActivity($resource, $startDateTime, $endDateTime, int $dayNum, $period = null, $activityType = null)
    {
        $activity = new Activity($resource, $activityType, $startDateTime, $endDateTime);
        $this->activities[] = $activity;
        $this->getDay($dayNum)->addActivity($resource, $activityType, date('H:i:s', strtotime($startDateTime)), date('H:i:s', strtotime($endDateTime)));
        if ($period) {
            $this->getDay($dayNum)->periods[$period]->addActivity($resource, $activityType, date('H:i:s', strtotime($startDateTime)), date('H:i:s', strtotime($endDateTime)));
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

    public function getArrivalDateTime()
    {
        if (isset($this->arrivalDateTime)) {
            return $this->arrivalDateTime;
        }
        $this->arrivalDateTime = date('Y-m-d H:i:s', strtotime($this->startDate . ' ' . self::DEFAULT_ARRIVAL_TIME));
        return $this->arrivalDateTime;
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function getDay(int $index = 0): Day
    {
        return $this->days[$index];
    }

    public function getDays(): array
    {
        return $this->days;
    }

    public function getDepartureDateTime()
    {
        if (isset($this->departureDateTime)) {
            return $this->departureDateTime;
        }
        $this->departureDateTime = date('Y-m-d H:i:s', strtotime($this->endDate . ' ' . self::DEFAULT_DEPARTURE_TIME));
        return $this->departureDateTime;
    }

    public function eventExists(): bool
    {
        return false;
    }

    public function trailExists(): bool
    {
        return false;
    }

    public function getUseFirstDay(): bool
    {
        $a = strtotime($this->getArrivalDateTime());
        $b = strtotime($this->startDate . ' ' . self::DEFAULT_ARRIVAL_TIME);
        return $a < $b - 3600;
    }

    public function getUseLastDay(): bool
    {
        $a = strtotime($this->getDepartureDateTime());
        $b = strtotime($this->endDate . ' ' . self::DEFAULT_DEPARTURE_TIME);
        return $a > $b + 3600;
    }

    public function setDays(array $days)
    {
        $this->days = $days;
    }
}