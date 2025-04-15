<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ImportTable
{
    public function importTable($table) {
        ini_set('memory_limit', '512M');

        $gz = base_path() . '/database/dumps/' . $table . '.sql.gz';
        $sql = base_path() . '/database/dumps/' . $table . '.sql';

        if (file_exists($gz)) {
            $cmd = 'gunzip -f ' . $gz;
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
            exec('gzip -f ' . $sql);
        }
    }
}
