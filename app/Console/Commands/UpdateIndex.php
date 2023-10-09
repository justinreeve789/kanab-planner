<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:index {--events} {--locations} {--restaurants} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the search engine';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('scout:index App\\Models\\Event');
        Artisan::call('scout:index App\\Models\\Location');
        Artisan::call('scout:index App\\Models\\ResourceText');
        Artisan::call('scout:index App\\Models\\Restaurant');
        Artisan::call('scout:index App\\Models\\Tour');
        Artisan::call('scout:index App\\Models\\Trail');

        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->option('trails');

        if ($doEvents) {
            $this->processEvents();
        }

        if ($doLocations) {
            $this->processLocations();
        }

        if ($doRestaurants) {
            $this->processRestaurants();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }
}
