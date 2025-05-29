<?php

namespace App\Traits\Itinerary;

use App\Models\Itinerary\Season;
use App\Models\Trail;

trait Preferences
{
    public function getActivityPriorities(): ?array
    {
        return $this->activity_priorities;
    }

    public function getAdventurousness(): ?float
    {
        return $this->adventurousness;
    }

    public function getAverageHikingspeed(): ?float
    {
        return $this->average_hiking_speed;
    }

    public function getBaseAreaId(): int
    {
        return $this->base_area_id;
    }

    public function getCheckIn()
    {
        return $this->check_in_time;
    }

    public function getCheckOut()
    {
        return $this->check_out_time;
    }

    public function getDurationDays(): ?int
    {
        return $this->duration_days;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getEventPriorities($random = false): ?array
    {
        if ($random === true) {
            return $this->shuffleArray($this->event_priorities);
        } else {
            return $this->event_priorities;
        }
    }
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLocationPriorities($random = false): ?array
    {
        if ($random === true) {
            return $this->shuffleArray($this->location_priorities);
        } else {
            return $this->location_priorities;
        }
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

    public function getRestaurantPriorities($random = false): ?array
    {
        if ($random === true) {
            return $this->shuffleArray($this->restaurant_priorities);
        } else {
            return $this->restaurant_priorities;
        }
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
        return $this->start_date;
    }

    public function getStrenuousness(): ?float
    {
        return $this->strenuousness;
    }

    public function getTrailPriorities($random = false): ?array
    {
        if ($random === true) {
            return $this->shuffleArray($this->trail_priorities);
        } else {
            return $this->trail_priorities;
        }
    }

    public function setActivityPriorities($priorities = null) {
        if ($priorities === null) {
            $priorities = self::DEFAULT_ACTIVITY_PRIORITIES;
        }
        if (!is_array($priorities)) {
            $priorities = explode(',', $priorities);
        }
        $this->activity_priorities = $priorities;
    }

    public function setAdventurousness(?float $adventurousness)
    {
//        $this->adventurousness = $adventurousness ?? self::DEFAULT_ADVENTUROUSNESS;
        $this->adventurousness = $adventurousness;
    }

    public function setAverageHikingspeed(?float $average_hiking_speed)
    {
        $this->average_hiking_speed = $average_hiking_speed ?? self::DEFAULT_AVERAGE_HIKING_SPEED;
    }

    public function setBaseAreaId(?int $baseAreaId)
    {
        if ($baseAreaId === null) {
            $baseAreaId = self::DEFAULT_BASE_AREA_ID;
        }
        $this->base_area_id = $baseAreaId;
    }

    public function setCheckIntime(?string $check_in_time)
    {
        if ($check_in_time === null) {
            $check_in_time = self::DEFAULT_CHECKIN_TIME;
        }
        $this->check_in_time = $check_in_time;
    }

    public function setCheckOutTime(?string $check_out_time)
    {
        if ($check_out_time === null) {
            $check_out_time = self::DEFAULT_CHECKOUT_TIME;
        }
        $this->check_out_time = $check_out_time;
    }

//    public function setDates($arrivalDateTime, $departureDateTime)
//    {
//        $this->arrival_date_time = $arrivalDateTime;
//        $this->departure_date_time = $departureDateTime;
//    }

    public function setDurationDays(?int $duration_days)
    {
        if ($duration_days === null) {
            $duration_days = self::DEFAULT_DURATION_DAYS;
        }
        $this->duration_days = $duration_days;
    }

    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;
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
        $this->start_date = $startDate;
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
        $this->trail_priorities = $priorities;
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
