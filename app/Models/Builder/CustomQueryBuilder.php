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
     * We want to get as close to all the properties as we can. For example, if adventurousness is 0.1 and strenuousness
     * is 0.9, we should weigh adv 0.2/str 0.8 higher than adv 0.5/str 0.5. We should evaluate the total deviation
     * rather than an average of the properties.
     * TODO: We may want to make this logarithmic, so preferences with higher values weigh heavier than lower values.
     * If a user has set a high adventurousness, it would make sense they'd want to see more events like this, even
     * if the deviation of the other properties skews the total out of place.
     */
    public function sortByDeviation(array $propValues)
    {
        $table = $this->getModel()->getTable();

        $order = '';
        $index = 0;
        foreach ($propValues as $prop => $value) {
            if (!is_numeric($prop) && $value !== null) {
                $order .= 'ABS(resource_data.' . $prop . ' - ' . $value . ')';
                if ($index < count($propValues) - 1) {
                    $order .= ' + ';
                }
            }
            $index++;
        }

        $query = $this->select($table . '.*')
            ->join('resource_data', function($join) use ($table) {
                $join->on($table . '.id', '=', 'resource_data.id');
        });

        if ($order != '') {
            $query->orderByRaw($order);
        }

        return $query;
    }

    /**
     * Calculate the proximity to the latitude/longitude coordinates using the Haversine formula.
     * This should come after other where() statements, both native and custom.
     */
    public function whereProximity($latitude, $longitude, $maxDistance, $orderBy = false)
    {
        $query = $this->whereRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude))) <= ?', [$latitude, $longitude, $latitude, $maxDistance]);
        if ($orderBy !== false) {
            $query->orderByRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))', [$latitude, $longitude, $latitude]);
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