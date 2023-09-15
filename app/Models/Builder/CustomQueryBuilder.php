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
        return $this->join('resource_data', function ($join) use ($table) {
            $join->on('resource_table', '=', \DB::raw('"' . $table . '"'));
            $join->on('resource_id', '=', \DB::raw($table . '.id'));
        })->orderByRaw($order);
    }

    public function whereProximity($latitude, $longitude, $maxDistance)
    {
        return $this->whereRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude))) <= ?', [$latitude, $longitude, $latitude, $maxDistance])
            ->orderByRaw('3959 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))', [$latitude, $longitude, $latitude]);
    }
}