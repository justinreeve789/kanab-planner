<?php

namespace App\Traits\Itinerary;

use App\Models\Itinerary\Season;

trait Preferences
{
    public function getActivityPriorities(): ?array
    {
        return $this->activityPriorities;
    }

    public function getAdventurousness(): ?float
    {
        return $this->adventurousness;
    }

    public function getAverageHikingSpeed(): ?float
    {
        return $this->averageHikingSpeed;
    }

    public function getBaseAreaId(): int
    {
        return $this->baseAreaId;
    }

    public function getCheckIn()
    {
        return $this->checkInTime;
    }

    public function getCheckOut()
    {
        return $this->checkOutTime;
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

    public function getPriorities(): ?array
    {
        return $this->priorities;
    }

    public function getSeason(): ?string
    {
        return $this->season;
    }

    public function getSolitariness(): ?float
    {
        return $this->solitariness;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getStrenuousness(): ?float
    {
        return $this->strenuousness;
    }

    public function getTrailPriorities($random = false): ?array
    {
        if ($random === true) {
            return $this->shuffleArray($this->trailPriorities);
        } else {
            return $this->trailPriorities;
        }
    }

    public function setActivityPriorities($priorities = null) {
        if ($priorities === null) {
            $priorities = self::DEFAULT_ACTIVITY_PRIORITIES;
        }
        if (!is_array($priorities)) {
            $priorities = explode(',', $priorities);
        }
        $this->activityPriorities = $priorities;
    }

    public function setAdventurousness(?float $adventurousness)
    {
//        $this->adventurousness = $adventurousness ?? self::DEFAULT_ADVENTUROUSNESS;
        $this->adventurousness = $adventurousness;
    }

    public function setAverageHikingSpeed(?float $averageHikingSpeed)
    {
        $this->averageHikingSpeed = $averageHikingSpeed ?? self::DEFAULT_AVERAGE_HIKING_SPEED;
    }

    public function setBaseAreaId(?int $baseAreaId)
    {
        if ($baseAreaId === null) {
            $baseAreaId = self::DEFAULT_BASE_AREA_ID;
        }
        $this->baseAreaId = $baseAreaId;
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
        $this->checkOutTime = $checkOutTime;
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

    public function setPriorities($priorities = null) {
        if ($priorities === null) {
            $priorities = self::DEFAULT_PRIORITIES;
        }
        if (!is_array($priorities)) {
            $priorities = explode(',', $priorities);
        }
        $this->priorities = $priorities;
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

    public function setSolitariness(?float $solitariness)
    {
//        $this->solitariness = $solitariness ?? self::DEFAULT_SOLITARINESS;
        $this->solitariness = $solitariness;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function setStrenuousness(?float $strenuousness)
    {
//        $this->strenuousness = $strenuousness ?? self::DEFAULT_STRENUOUSNESS;
        $this->strenuousness = $strenuousness;
    }

    public function setTrailPriorities($priorities = null) {
        if ($priorities === null) {
            $priorities = self::DEFAULT_TRAIL_PRIORITIES;
        }
        if (!is_array($priorities)) {
            $priorities = explode(',', $priorities);
        }
        $this->trailPriorities = $priorities;
    }

    protected function shuffleArray($array) {
        $keys = array_keys($array);
        shuffle($keys);
        $shuffledArray = [];
        foreach ($keys as $key) {
            $shuffledArray[$key] = $array[$key];
        }
        return $shuffledArray;
    }
}
