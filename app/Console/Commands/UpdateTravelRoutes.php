<?php

namespace App\Console\Commands;

use App\Models\Accommodation;
use App\Models\Area;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\TravelRoute;
use App\ResourceEnum;
use App\Services\LocationService;
use Illuminate\Console\Command;

class UpdateTravelRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:travel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the travel route distances.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locationService = new LocationService();
        $locations = Location::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $trails = Trail::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $coordinates = [];
        foreach ($locations as $resource) {
            $coordinates[$resource->latitude . '|' . $resource->longitude] = (object) [
                'area_id' => $resource->area->id,
                'lat' => $resource->latitude,
                'lng' => $resource->longitude,
            ];
        }
        foreach ($trails as $resource) {
            $coordinates[$resource->latitude . '|' . $resource->longitude] = (object) [
                'area_id' => $resource->area->id,
                'lat' => $resource->latitude,
                'lng' => $resource->longitude,
            ];
        }
        foreach ($coordinates as $c1) {
            foreach ($coordinates as $c2) {
                if ($c1->area_id === $c2->area_id || $c1->area_id === Area::KANAB || $c2->area_id === Area::KANAB) {
                    $travelRoute = TravelRoute::where([
                        'lat_origin' => $c1->lat,
                        'lng_origin' => $c1->lng,
                        'lat_dest' => $c2->lat,
                        'lng_dest' => $c2->lng,
                    ])->first();
                    if (!$travelRoute) {
                        $drivingDetails = $locationService->estimateDrivingDetails($c1->lat, $c1->lng, $c2->lat, $c2->lng);
                        TravelRoute::updateOrCreate([
                            'lat_origin' => $c1->lat,
                            'lng_origin' => $c1->lng,
                            'lat_dest' => $c2->lat,
                            'lng_dest' => $c2->lng,
                        ], [
                            'distance_miles' => $drivingDetails->miles,
                            'duration_hours' => $drivingDetails->hours,
                        ]);
                        TravelRoute::updateOrCreate([
                            'lat_origin' => $c2->lat,
                            'lng_origin' => $c2->lng,
                            'lat_dest' => $c1->lat,
                            'lng_dest' => $c1->lng,
                        ], [
                            'distance_miles' => $drivingDetails->miles,
                            'duration_hours' => $drivingDetails->hours,
                        ]);
                    }
                }
            }
        }
    }
}
