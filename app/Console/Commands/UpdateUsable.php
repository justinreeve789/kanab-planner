<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Services\ImageService;
use Illuminate\Console\Command;

class UpdateUsable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:usable {--events} {--locations} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag the is_usable status if necessary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doTrails = $this->option('trails');

        if ($doTrails !== false) {
            $this->doTrails();
        }
    }

    protected function doTrails()
    {
        // Make sure trails are within 40 miles of an area, unless it's the Grand Canyon.
        $trails = Trail::with('area')->get();
        foreach ($trails as $trail) {
            if ($trail->latitude && $trail->longitude) {
                if ($trail->area->id !== Area::NORTH_RIM) {
                    $distanceMiles = $trail->calculateDistance($trail->area->latitude, $trail->area->longitude);
                    if ($distanceMiles >= 40 || ($trail->area->id === Area::ZION && $trail->longitude < -113.2722)) {
                        $trail->is_usable = 0;
                        $trail->save();
                    }
                }
            }
        }
    }
}
