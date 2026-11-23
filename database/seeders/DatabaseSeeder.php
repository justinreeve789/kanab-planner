<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference rows (areas, activity types, the home accommodation) are created by the migrations; this loads the
     * full dataset on top.
     */
    public function run(): void
    {
        $this->call(DumpSeeder::class);
    }
}
