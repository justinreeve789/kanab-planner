<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ImportTable
{
    /**
     * Load database/dumps/{$table}.sql(.gz) into the table. Set DB_IMPORT_DUMPS=false to migrate an empty schema
     * (the test suite does this so it can seed its own deterministic fixtures).
     */
    public function importTable($table) {
        if (!filter_var(env('DB_IMPORT_DUMPS', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        ini_set('memory_limit', '512M');

        $gz = base_path() . '/database/dumps/' . $table . '.sql.gz';
        $sql = base_path() . '/database/dumps/' . $table . '.sql';

        if (file_exists($gz)) {
            $cmd = 'gunzip -f -k ' . $gz;
            exec($cmd);
        }

        if (file_exists($sql)) {
            $file = fopen($sql, 'r');
            while ($line = fgets($file)) {
                if (trim($line) === '') {
                    continue;
                }
                DB::unprepared($line);
            }
            fclose($file);
            if (!file_exists($gz)) {
                exec('gzip -f -k ' . $sql);
            }
            unlink($sql);
        }
    }
}
