<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;

class ImportMapTiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:maptiles {--type=} {--ext=} {--x=} {--y=} {--z=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import map tiles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ext = $this->option('ext') ?? 'png';
        $mapType = $this->option('type');
        $startX = $this->option('x');
        $startY = $this->option('y');
        $startZ = $this->option('z');
        $imageService = new ImageService();

        $lat1 = 37.02336; // Lower-left latitude
        $lon1 = -113.2100; // Lower-left longitude
        $lat2 = 37.62665; // Upper-right latitude
        $lon2 = -111.44025; // Upper-right longitude

        // Range of zoom levels (inclusive)
        $minZoom = 9;  // Starting zoom level
        $maxZoom = 18;  // Ending zoom level

        // Iterate over the zoom levels in the range
        for ($zoom = $minZoom; $zoom <= $maxZoom; $zoom++) {
            if (is_numeric($startZ) && $zoom < $startZ) {
                continue;
            }
            $startZ = null;

            // Convert the latitude and longitude bounds to tile coordinates for the current zoom level
            $tile1 = $imageService->latLngToMapTile($lat1, $lon1, $zoom);
            $tile2 = $imageService->latLngToMapTile($lat2, $lon2, $zoom);

            // Get the tile range (inclusive)
            $minX = $tile1['x'];
            $maxX = $tile2['x'];
            $minY = $tile1['y'];
            $maxY = $tile2['y'];

            // Output the tile coordinates for each tile in the region at this zoom level
            $count = 0;
            for ($x = $minX; $x <= $maxX; $x++) {
                if (is_numeric($startX) && $x < $startX) {
                    continue;
                }
                $startX = null;
                for ($y = $minY; $y >= $maxY; $y--) {
                    if (is_numeric($startY) && $y > $startY) {
                        continue;
                    }
                    $startY = null;
                    echo "Tile ($x, $y)\n";
                    $imageService->getMapTile($mapType, $x, $y, $zoom, $ext);
                    $count++;
                }
            }
        }
    }
}
