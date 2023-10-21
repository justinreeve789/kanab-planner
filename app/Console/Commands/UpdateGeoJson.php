<?php

namespace App\Console\Commands;

use App\Models\Trail;
use App\Models\TrailGeoJson;
use Illuminate\Console\Command;

class UpdateGeoJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:geojson';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the GeoJson data for the trails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $trails = Trail::whereDoesntHave('geoJson')->get();
        foreach ($trails as $trail) {
            $file = storage_path('geojson/' . $trail->id . '-' . $trail->source_id . '.geojson');
            if (file_exists($file)) {
                $geoJson = file_get_contents($file);
                if ($geoJson) {
                    TrailGeoJson::updateOrCreate(['trail_id' => $trail->id], [
                        'geojson' => $geoJson,
                    ]);
                }
            }
        }
    }
}
