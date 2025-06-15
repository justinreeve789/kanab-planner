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
            $query->whereRaw("DATE_FORMAT(?, '%Y-%m-%d %h-%i-%s') >= COALESCE(event_dates.start_date, DATE_FORMAT(event_dates.start_date, '%Y-%m-%d 10:00:00'))", [$startDateTime]);
            $query->whereRaw("DATE_FORMAT(?, '%Y-%m-%d %h-%i-%s') <= COALESCE(
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

    public function whereTravelDistance($latitude, $longitude, $maxDistance, $orderBy = false, $table = null)
    {
        $travelRoutes = DB::table('travel_routes as tr')
            ->select(
                't1.name AS origin_trail_name',
                't2.name AS dest_trail_name',
                'tr.*' // Select all columns from the travel_routes table
            )
            ->distinct() // Apply DISTINCT to the entire selection
            ->join('trails as t1', function ($join) {
                $join->on('t1.latitude', '=', 'tr.lat_origin')
                    ->on('t1.longitude', '=', 'tr.lng_origin');
            })
            ->join('trails as t2', function ($join) {
                $join->on('t2.latitude', '=', 'tr.lat_dest')
                    ->on('t2.longitude', '=', 'tr.lng_dest');
            })
            ->where('t1.id', '!=', DB::raw('t2.id')) // Use DB::raw for cross-table comparison
            ->where('t1.area_id', $areaId)
            ->where('t1.area_id', DB::raw('t2.area_id')) // Use DB::raw for cross-table comparison
            ->orderBy('tr.distance_miles')
            ->get();
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