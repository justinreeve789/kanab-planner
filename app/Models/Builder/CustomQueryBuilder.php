<?php

namespace App\Models\Builder;

use Illuminate\Database\Eloquent\Builder;

class CustomQueryBuilder extends Builder
{
    public function sortByDeviation($prop, $value)
    {
        $table = $this->getModel()->getTable();
        return $this->join('resource_data', function($join) use ($table) {
            $join->on('resource_table', '=', \DB::raw('"' . $table . '"'));
            $join->on('resource_id', '=', \DB::raw($table . '.id'));
        })->orderByRaw('ABS(resource_data.' . $prop . ' - ' . $value . ')');
    }

    /**
     * Get the table name of a related model.
     *
     * @param string $relation
     * @return string
     */
    protected function getRelationTable($relation)
    {
        $related = $this->getModel()->{$relation}()->getRelated();
        return $related->getTable();
    }
}