<?php

namespace App\Models\Builder;

use Illuminate\Database\Eloquent\Builder;

class CustomQueryBuilder extends Builder
{
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
        $order = '0';
        foreach ($propValues as $prop => $value) {
            if ($value !== null) {
                $order .= ' + ABS(resource_data.' . $prop . ' - ' . $value . ')';
            }
        }
        return $this->join('resource_data', function($join) use ($table) {
            $join->on('resource_table', '=', \DB::raw('"' . $table . '"'));
            $join->on('resource_id', '=', \DB::raw($table . '.id'));
        })->orderByRaw($order);
    }

    /**
     * Calculate the proximity to the latitude/longtitude coordinates using the Haversine formula.
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
                    ->whereRaw('location_hours.time_open <= TIME(?)', [$time])
                    ->whereRaw('location_hours.time_close >= TIME(?)', [$time])
                    ->orWhere(function($query) use ($dayOfWeek, $time) {
                        $query->where(function($query) use ($dayOfWeek) {
                            $query->whereNull('location_hours.id')
                                ->orWhereNotIn('location_hours.day_of_week', [$dayOfWeek]);
                        })->whereRaw('TIME(?) BETWEEN TIME("06:00") AND TIME("22:00")', [$time]);
                    })
                    ->select('locations.*')
                    ->distinct();
                break;
            case 'restaurants':
                break;
            case 'trails':
            default:
        }
    }
}