<?php

namespace App\Console\Commands;

use App\Jobs\UpdateBusyJob;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Console\Command;

class UpdateBusy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:busy {--events} {--locations} {--restaurants} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the busyness of resources';

    private $locationService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->locationService = new LocationService();

        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->option('trails');

        if ($doLocations) {
            $this->processLocations();
//            UpdateBusyJob::dispatch();
        }
    }

    protected function processLocations()
    {
        $locations = Location::where('source_type', 'googleplaces')->where('is_usable', 1)->get();
        foreach ($locations as $location) {
            $this->locationService->updateBusynessForLocation($location);
        }
    }
}
