<?php

namespace App\Traits\Itinerary;

use App\Models\Itinerary\Season;

trait Preferences
{
    public function getAdventurousness(): float
    {
        return $this->adventurousness;
    }

    public function getCheckIn()
    {
        return $this->checkInTime;
    }

    public function getCheckOut()
    {
        return $this->checkOut;
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

    public function getSolitariness(): float
    {
        return $this->solitariness;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getStrenuousness(): float
    {
        return $this->strenuousness;
    }

    public function setAdventurousness(?float $adventurousness)
    {
        $this->adventurousness = $adventurousness ?? self::DEFAULT_ADVENTUROUSNESS;
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
        $this->solitariness = $solitariness ?? self::DEFAULT_SOLITARINESS;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function setStrenuousness(?float $strenuousness)
    {
        $this->strenuousness = $strenuousness ?? self::DEFAULT_STRENUOUSNESS;
    }
}
