<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exit code 0 when the dataset (database/dumps) has been loaded, 1 when the trails table is empty. Used by the
 * Docker entrypoint to seed a new database once.
 */
class HasData extends Command
{
    protected $signature = 'app:has-data';

    protected $description = 'Succeed if the trip dataset is loaded (used by docker/entrypoint.sh)';

    public function handle(): int
    {
        $loaded = Schema::hasTable('trails') && DB::table('trails')->exists();
        $this->line($loaded ? 'The dataset is loaded.' : 'The dataset is empty.');
        return $loaded ? self::SUCCESS : self::FAILURE;
    }
}
