<?php

namespace App\Models\Itinerary;

use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Traits\Itinerary\Preferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary
{
    use Preferences;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;
    const DEFAULT_CHECKIN_TIME = '16:00';
    const DEFAULT_CHECKOUT_TIME = '10:00';
    const DEFAULT_DURATION_DAYS = 5;
    const DEFAULT_PRIORITIES = ['trails', 'events', 'locations', 'restaurants'];
    const DEFAULT_SEASON = 'summer';
    const FALL = 'fall';
    const SPRING = 'spring';
    const SUMMER = 'summer';
    const WINTER = 'winter';

    private $accommodationPreference;
    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    private $arrivalDateTime;
    private $checkInTime;
    private $checkOutTime;
    private $days;
    private $departureDateTime;
    private $durationDays;
    private $endDate;
    private $estimatedCost;
    private $homeLocationId;
    private $interests;
    private $latitude;  // The latitude of the "base camp."
    private $longitude; // The longitude of the "base camp."
    private $month;
    private array $priorities;
    private string $season;
    public $solitariness = self::DEFAULT_SOLITARINESS;
    private $startDate;
    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    private $tripPreference;

    public function __construct()
    {
        $this->days = [];
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

    public function getDay($index = 0): Day
    {
        return $this->days[$index];
    }

    public function getDays(): array
    {
        return $this->days;
    }
}