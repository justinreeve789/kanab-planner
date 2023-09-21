<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Services\ImageService;
use Illuminate\Console\Command;

class UpdateImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:images {--events} {--locations} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates images';

    private $imageService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doTrails = $this->option('trails');

        $this->imageService = new ImageService();

        if ($doLocations !== false) {
            $this->doLocations();
        }

        if ($doTrails !== false) {
            $this->doTrails();
        }
    }

    protected function doLocations()
    {
        $locations = Location::whereDoesntHave('images')->get();
        foreach ($locations as $loc) {
            $images = $this->imageService->getPhotos($loc->area->name . ' ' . $loc->name);
            foreach ($images as $image) {
                try {
                    LocationImage::updateOrCreate(['location_id' => $loc->id, 'url' => $image->url]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $this->error('Skipped ' . $image->url);
                }
            }
        }
    }

    protected function doTrails()
    {
        $trails = Trail::whereDoesntHave('images')->get();
        foreach ($trails as $item) {
            $area = Area::where('id', $item->area_id)->first();
            $images = $this->imageService->getPhotos($area->name . ' ' . $item->name . ' trail');
            foreach ($images as $image) {
                try {
                    TrailImage::updateOrCreate(['trail_id' => $item->id, 'url' => $image->url]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $this->error('Skipped ' . $image->url);
                }
            }
        }
    }
}
