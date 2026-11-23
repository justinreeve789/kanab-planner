<?php

namespace App\Console\Commands;

use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Services\TrailService;
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
        $trailService = new TrailService();
        $trails = Trail::get();
        foreach ($trails as $trail) {
            $file = storage_path('geojson/' . $trail->id . '-' . $trail->source_id . '.geojson');
            if (file_exists($file)) {
                $geoJson = file_get_contents($file);
                // A track with fewer than two points can't be drawn or timed (trail 444 was stored that way).
                if ($geoJson && count($trailService->extractTrailPoints($geoJson)) >= 2) {
                    $geoJson = json_encode(json_decode($geoJson));
                    TrailGeoJson::updateOrCreate(['trail_id' => $trail->id], [
                        'geojson' => $geoJson,
                    ]);
                } else {
                    $this->warn("Skipping trail {$trail->id}: {$file} has no usable coordinates.");
                }
            }
        }
    }
}
