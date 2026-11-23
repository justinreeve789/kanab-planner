<?php

namespace App\Models\Builder;

use Illuminate\Database\Eloquent\Builder;

class CustomQueryBuilder extends Builder
{
    /**
     * Events with an occurrence (event_dates row) that overlaps [$startDateTime, $endDateTime). See
     * EventDate::scopeOverlapping() for how open-ended occurrences are bounded.
     *
     * (This used to require the occurrence to contain the whole window, so a 9–12 market never matched a 6–11
     * morning, and it compared times on a 12-hour clock.)
     */
    public function betweenDates($startDateTime, $endDateTime)
    {
        return $this->whereHas('dates', fn($query) => $query->overlapping($startDateTime, $endDateTime));
    }

    /**
     * Calculate the proximity to the latitude/longitude coordinates using the Haversine formula.
     * This should come after other where() statements, both native and custom.
     */
    public function whereProximity($latitude, $longitude, $maxDistance, $orderBy = false, $table = null)
    {
        if ($table !== null) {
            $table .= '.';
        }

        $query = $this->whereRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(' . $table . 'latitude)) * COS(RADIANS(' . $table . 'longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(' . $table . 'latitude))) <= ?', [$latitude, $longitude, $latitude, $maxDistance]);
        if ($orderBy !== false) {
            $query->orderByRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(' . $table . 'latitude)) * COS(RADIANS(' . $table . 'longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(' . $table . 'latitude)))', [$latitude, $longitude, $latitude]);
        }
        return $query;
    }

    /**
     * Only rows that are open at $dateTime, by location_hours (day_of_week 0 = Sunday; a close time earlier than the
     * open time means it closes after midnight). Places with no hours recorded at all are treated as open between
     * 08:00 and 20:00 when $defaultIsOpen is true, and excluded otherwise. Events and trails have no opening hours,
     * so they're returned unfiltered.
     */
    public function whereIsOpen($dateTime, $defaultIsOpen = true)
    {
        $table = $this->getModel()->getTable();
        $locationColumn = match ($table) {
            'locations' => 'locations.id',
            'restaurants' => 'restaurants.location_id',
            default => null,
        };
        if ($locationColumn === null) {
            return $this;
        }

        $dayOfWeek = (int) date('w', strtotime($dateTime));
        $previousDay = ($dayOfWeek + 6) % 7;
        $time = date('H:i:s', strtotime($dateTime));

        return $this->where(function($query) use ($locationColumn, $dayOfWeek, $previousDay, $time, $defaultIsOpen) {
            $query->whereExists(function($hours) use ($locationColumn, $dayOfWeek, $previousDay, $time) {
                $hours->from('location_hours')
                    ->whereColumn('location_hours.location_id', $locationColumn)
                    ->where(function($open) use ($dayOfWeek, $previousDay, $time) {
                        // Same-day hours, e.g. 07:00–22:00.
                        $open->where(fn($q) => $q->where('day_of_week', $dayOfWeek)
                            ->whereColumn('time_close', '>', 'time_open')
                            ->where('time_open', '<=', $time)->where('time_close', '>', $time))
                        // Opens today and runs past midnight, e.g. 17:00–02:00 at 21:00.
                        ->orWhere(fn($q) => $q->where('day_of_week', $dayOfWeek)
                            ->whereColumn('time_close', '<=', 'time_open')
                            ->where('time_open', '<=', $time))
                        // Opened yesterday and still open, e.g. 17:00–02:00 at 01:00.
                        ->orWhere(fn($q) => $q->where('day_of_week', $previousDay)
                            ->whereColumn('time_close', '<=', 'time_open')
                            ->where('time_close', '>', $time));
                    });
            });
            if ($defaultIsOpen && $time >= '08:00:00' && $time < '20:00:00') {
                $query->orWhereNotExists(fn($hours) => $hours->from('location_hours')
                    ->whereColumn('location_hours.location_id', $locationColumn));
            }
        });
    }
}