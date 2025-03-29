<?php

namespace App\Repositories;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResourceRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return ResourceRepository::class;
    }

    public function combineResources(string $modelClass, $primary = null, $secondary = null) {
        if (!$primary || !$secondary) {
            return false;
        }

        $model = new $modelClass();

        // Get the column names for the table
        $columnNames = DB::getSchemaBuilder()->getColumnListing($model->getTable());
        $fieldValues = [];

        foreach ($columnNames as $columnName) {
            // Exclude the primary key column.
            if ($columnName !== 'id') {
                // If there's a primary value, use it; otherwise, use the secondary value.
                $fieldValues[$columnName] = $primary->$columnName ?? $secondary->$columnName;
            }
        }

        $fieldValues['source_type'] = 'merge';
        $fieldValues['source_id'] = $primary->id . ',' . $secondary->id;
        $fieldValues['is_merged'] = true;

        return $model::create($fieldValues);
    }
}
