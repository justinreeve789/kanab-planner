<?php

namespace App\Models\Itinerary;

use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary
{
    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;
    const DEFAULT_CHECKIN_TIME = '16:00';
    const DEFAULT_CHECKOUT_TIME = '10:00';
    const DEFAULT_DURATION_DAYS = 5;
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
    private int $month;
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

    public function getCheckIn()
    {
        return $this->checkInTime;
    }

    public function getCheckOut()
    {
        return $this->checkOut;
    }

    public function getDay($index = 0): Day
    {
        return $this->days[$index];
    }

    public function getDays(): array
    {
        return $this->days;
    }

    public function getDurationDays(): ?int
    {
        return $this->durationDays;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function getSeason(): ?string
    {
        return $this->season;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setCheckInTime(?string $checkInTime)
    {
        if ($checkInTime === null) {
            $checkInTime = self::DEFAULT_CHECKIN_TIME;
        }
        $this->checkInTime = $checkInTime;
    }

    public function setCheckOutTime(?string $checkOutTime)
    {
        if ($checkOutTime === null) {
            $checkOutTime = self::DEFAULT_CHECKOUT_TIME;
        }
        $this->checkOut = $checkOutTime;
    }

    public function setDates($arrivalDateTime, $departureDateTime)
    {
        $this->arrivalDateTime = $arrivalDateTime;
        $this->departureDateTime = $departureDateTime;
    }

    public function setDurationDays(?int $durationDays)
    {
        if ($durationDays === null) {
            $durationDays = self::DEFAULT_DURATION_DAYS;
        }
        $this->durationDays = $durationDays;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    public function setLatitude(?float $latitude)
    {
        $this->latitude = $latitude;
    }

    public function setLongitude(?float $longitude)
    {
        $this->longitude = $longitude;
    }

    public function setMonth(?int $month)
    {
        if ($month >= 1 && $month <= 12) {
            $this->month = $month;
        }
    }

    public function setSeason(?string $season)
    {
        // Determine the season from a start date if one exists.
        if ($this->getStartDate()) {
            $this->season = Season::get($this->getStartDate());
        } else if (in_array($season, [self::FALL, self::SPRING, self::SUMMER, self::WINTER])) {
            $this->season = $season;
        } else {
            $this->season = Season::get('+2 weeks');
        }
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }
}