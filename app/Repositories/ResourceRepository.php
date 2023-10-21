<?php

namespace App\Repositories;

use Illuminate\Database\Connection;
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

    public function combineResources($table, $primary = null, $secondary = null) {
        if (!$primary || !$secondary) {
            return false;
        }
        $columnNames = DB::getSchemaBuilder()->getColumnListing($table);
        $fieldUpdates = [];

        foreach ($columnNames as $columnName) {
            if ($columnName !== 'id') { // Exclude the primary key column
                $fieldUpdates[] = "`primary`.$columnName = COALESCE(`primary`.$columnName, secondary.$columnName)";
            }
        }

        $updateQuery = "UPDATE " . $table . " AS `primary`, " . $table . " AS secondary
                        SET " . implode(', ', $fieldUpdates) . "
                        WHERE `primary`.id = ? AND secondary.id = ?";
        DB::update($updateQuery, [$primary->id, $secondary->id]);
    }
}
