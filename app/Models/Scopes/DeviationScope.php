<?php

namespace App\Models\Scopes;

use App\Models\ResourceData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DeviationScope implements Scope
{
    /**
     * We want to get as close to all the properties as we can. For example, if adventurousness is 0.1 and strenuousness
     * is 0.9, we should weigh adv 0.2/str 0.8 higher than adv 0.5/str 0.5. We should evaluate the total deviation
     * rather than an average of the properties.
     * TODO: We may want to make this logarithmic, so preferences with higher values weigh heavier than lower values.
     * If a user has set a high adventurousness, it would make sense they'd want to see more events like this, even
     * if the deviation of the other properties skews the total out of place.
     */
    public function apply(Builder $builder, Model $model)
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
                $join->on($table . '.id', '=', 'resource_data.resource_id');
            });

        if ($order != '') {
            $query->orderByRaw($order);
        }

        return $query;
    }
}
