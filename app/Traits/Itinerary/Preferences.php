<?php

namespace App\Traits\Itinerary;

use App\Models\Itinerary\Season;

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

    public function getEventPriorities($random = false): array
    {
        $ids = $this->event_priorities ?? [];
        return $random === true ? $this->shuffleArray($ids) : $ids;
    }
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLocationPriorities($random = false): array
    {
        $ids = $this->location_priorities ?? [];
        return $random === true ? $this->shuffleArray($ids) : $ids;
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

    public function getRestaurantPriorities($random = false): array
    {
        $ids = $this->restaurant_priorities ?? [];
        return $random === true ? $this->shuffleArray($ids) : $ids;
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

    public function getTrailPriorities($random = false): array
    {
        $ids = $this->trail_priorities ?? [];
        return $random === true ? $this->shuffleArray($ids) : $ids;
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

    /** Null means "whatever the lodging says" (see ItineraryService::fillArrivalDay()). */
    public function setCheckIntime(?string $check_in_time)
    {
        $this->check_in_time = $check_in_time;
    }

    public function setCheckOutTime(?string $check_out_time)
    {
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

    public function setTrailPriorities($ids = null)
    {
        $this->trail_priorities = $this->normalizeIds($ids);
    }

    public function setEventPriorities($ids = null)
    {
        $this->event_priorities = $this->normalizeIds($ids);
    }

    public function setLocationPriorities($ids = null)
    {
        $this->location_priorities = $this->normalizeIds($ids);
    }

    public function setRestaurantPriorities($ids = null)
    {
        $this->restaurant_priorities = $this->normalizeIds($ids);
    }

    public function setAreaPriorities($ids = null)
    {
        $this->area_priorities = $this->normalizeIds($ids);
    }

    public function getAreaPriorities(): array
    {
        return $this->area_priorities ?? [];
    }

    /**
     * Accepts an array or a comma-separated string; returns unique positive integer ids.
     */
    protected function normalizeIds($ids): array
    {
        if ($ids === null || $ids === '') {
            return [];
        }
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }

    protected function shuffleArray(array $array) {
        $keys = array_keys($array);
        shuffle($keys);
        $shuffledArray = [];
        foreach ($keys as $key) {
            $shuffledArray[$key] = $array[$key];
        }
        return $shuffledArray;
    }
}
