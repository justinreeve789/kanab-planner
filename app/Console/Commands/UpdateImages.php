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
    protected $signature = 'update:images {--events} {--locations} {--trails} {--gps}';

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
        $doGps = $this->option('gps');

        $this->imageService = new ImageService();

        if ($doLocations !== false) {
            $this->doLocations();
        }

        if ($doTrails !== false) {
            $this->doTrails($doGps);
        }
    }

    protected function doLocations()
    {
//        $locations = Location::whereDoesntHave('images')->get();
//        foreach ($locations as $loc) {
//            $images = $this->imageService->getPhotos($loc->name . ' ' . $loc->address);
//            foreach ($images as $image) {
//                try {
//                    LocationImage::updateOrCreate(['location_id' => $loc->id, 'url' => $image->url]);
//                } catch (\Illuminate\Database\QueryException $e) {
//                    $this->error('Skipped ' . $image->url);
//                }
//            }
//        }

        $locations = Location::whereHas('images')->get();
        foreach ($locations as $item) {
            foreach ($item->images as $image) {
                if (!str_contains($image->local_url, 'visitkanab/images')) {
                    $imageFile = $this->imageService->storeImage($image->url);
                    if ($imageFile) {
                        $image->local_url = $imageFile['path'];
                        $image->size = $imageFile['size'];
                        $image->save();
                    } else if ($imageFile === false) {
                        echo 'Deleting ' . $image->url . '
';
                        $image->delete();
                    }
                }
            }
        }
    }

    protected function doTrails($gps = false)
    {
//        $trails = Trail::whereDoesntHave('images')->get();
//        foreach ($trails as $item) {
//            $area = Area::where('id', $item->area_id)->first();
//            $images = $this->imageService->getPhotos($area->name . ' ' . $item->name . ' trail');
//            foreach ($images as $image) {
//                try {
//                    TrailImage::updateOrCreate(['trail_id' => $item->id, 'url' => $image->url]);
//                } catch (\Illuminate\Database\QueryException $e) {
//                    $this->error('Skipped ' . $image->url);
//                }
//            }
//        }

        $trails = Trail::whereHas('images')->get();
        foreach ($trails as $item) {
            foreach ($item->images as $image) {
                // Extract GPS data.
                if ($gps === true) {
                    $latLng = $this->imageService->getLatLng($image->url);
                    if (!$latLng) {
                        continue;
                    } else {
                        TrailImage::updateOrCreate(['id' => $image->id], [
                            'latitude' => $latLng['latitude'],
                            'longitude' => $latLng['longitude'],
                        ]);
                    }
                }

                // Store in the cloud.
                if (!str_contains($image->local_url, 'visitkanab/images')) {
                    $imageFile = $this->imageService->storeImage($image->url);
                    if ($imageFile) {
                        $image->local_url = $imageFile['path'];
                        $image->size = $imageFile['size'];
                        $image->save();
                    } else if ($imageFile === false) {
                        echo 'Deleting ' . $image->url . '
';
                        $image->delete();
                    }
                }

                // Create a thumbnail.
                if (str_contains($image->local_url, 'visitkanab/images') && !str_contains($image->thumbnail_local_url, 'visitkanab/images')) {
                    $resizedImageFile = $this->imageService->resizeImage(getenv('APP_URL') . '/image/trail/' . $image->id);
                    if ($resizedImageFile) {
                        $imageFile = $this->imageService->storeThumbnail($resizedImageFile, 'thumb_' . pathinfo($image->local_url, PATHINFO_BASENAME));
                        if ($imageFile) {
                            unlink($resizedImageFile);
                            $image->thumbnail_local_url = $imageFile['path'];
                            $image->thumbnail_size = $imageFile['size'];
                            $image->save();
                        }
                    }
                }
            }
        }
    }
}
