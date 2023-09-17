<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use Illuminate\Console\Command;

class UpdateAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:areas {--locations} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update area ids based on latitude and longitude.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doLocations = $this->option('locations');
        $doTrails = $this->options('trails');

        if ($doLocations) {
            $this->processLocations();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    protected function processLocations()
    {
        $locations = Location::where('area_id', '=', 0)->whereNotNull('latitude')->whereNotNull('longitude')->get();
        foreach ($locations as $item) {
            $area = Area::getAreaForLatLng($item->latitude, $item->longitude);
            if ($area) {
                $item->area_id = $area->id;
                $item->save();
            }
        }
    }

    protected function processTrails()
    {
        $trails = Trail::where('area_id', '=', 0)->whereNotNull('latitude')->whereNotNull('longitude')->get();
        foreach ($trails as $item) {
            $area = Area::getAreaForLatLng($item->latitude, $item->longitude);
            if ($area) {
                $item->area_id = $area->id;
                $item->save();
            }
        }
    }
}
