<?php

namespace App\Models\Builder;

use Illuminate\Database\Eloquent\Builder;

class CustomQueryBuilder extends Builder
{
    /**
     * Some events might be all-day where the user could attend at any time, while others are certain hours where the
     * user should attend at the start. If there's no start date, assume it starts as 10am. If there's no end date,
     * assume it ends at 10pm, or within 4 hours, whichever is earliest, unless it starts at 10pm or later, in which
     * case it ends at midnight (23:59:59), regardless of any specified end date.
     */
    public function betweenDates($startDateTime, $endDateTime)
    {
        return $this->whereHas('dates', function($query) use ($startDateTime, $endDateTime) {
            // The bound values used to go through DATE_FORMAT(?, '%Y-%m-%d %h-%i-%s'): %h is the 12-hour clock, so a
            // 17:00 window was compared as 05:00. Compare the datetimes directly.
            $query->whereRaw("CAST(? AS DATETIME) >= event_dates.start_date", [$startDateTime]);
            $query->whereRaw("CAST(? AS DATETIME) <= COALESCE(
                event_dates.end_date,
                CASE
                    WHEN TIME(event_dates.start_date) >= '22:00:00'
                        THEN DATE_FORMAT(event_dates.start_date, '%Y-%m-%d 23:59:59')
                    ELSE
                        LEAST(
                            DATE_FORMAT(event_dates.start_date, '%Y-%m-%d 22:00:00'),
                            ADDTIME(event_dates.start_date, '04:00:00')
                        )
                END
            )", [$endDateTime]);
        });
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
     * Determine if the event, location, restaurant, or trail is open on the specified date within the time range.
     * If we don't have hours for something, and $defaultIsOpen is set to true, we'll just assume it's open if it's
     * between the hours of 08:00 and 20:00.
     */
    public function whereIsOpen($dateTime, $defaultIsOpen = true)
    {
        $table = $this->getModel()->getTable();
        switch ($table) {
            case 'events':
                break;
            case 'locations':
                $dayOfWeek = date('w', strtotime($dateTime));
                $time = date('H:i:s', strtotime($dateTime));
                return $this->leftJoin('location_hours', 'location_hours.location_id', '=', 'locations.id')
                    ->where('location_hours.day_of_week', '=', $dayOfWeek)
                    ->whereRaw('COALESCE(location_hours.time_open, TIME("06:00")) <= TIME(?)', [$time])
                    ->whereRaw('COALESCE(location_hours.time_close, TIME("22:00")) >= TIME(?)', [$time])
                    ->select('locations.*')
                    ->distinct();
                break;
            case 'restaurants':
                $dayOfWeek = date('w', strtotime($dateTime));
                $time = date('H:i:s', strtotime($dateTime));
                return $this->leftJoin('location_hours', 'location_hours.location_id', '=', 'restaurants.location_id')
                    ->where('location_hours.day_of_week', '=', $dayOfWeek)
                    ->whereRaw('COALESCE(location_hours.time_open, TIME("06:00")) <= TIME(?)', [$time])
                    ->whereRaw('COALESCE(location_hours.time_close, TIME("22:00")) >= TIME(?)', [$time])
                    ->select('restaurants.*')
                    ->distinct();
                break;
            case 'trails':
            default:
        }
    }
}