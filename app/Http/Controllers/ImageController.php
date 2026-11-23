<?php

namespace App\Http\Controllers;

use App\Models\AccommodationImage;
use App\Models\EventImage;
use App\Models\LocationImage;
use App\Models\TrailImage;
use App\Services\ImageService;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    private $imageService;

    public function __construct() {
        $this->imageService = new ImageService();
    }

    public function getImageByQuery(Request $request) {
        $query = $request->get('q');
        return response()->json($this->imageService->search($query));
    }

    public function getUrl(Request $request, string $type, string $id, bool $thumbnail = false) {
        $size = $request->get('size');

        switch ($type) {
            case 'accommodation':
                $model = AccommodationImage::class;
                break;
            case 'event':
                $model = 'App\\Models\\EventImage';
                break;
            case 'location':
            case 'restaurant':
                $model = 'App\\Models\\LocationImage';
                break;
            case 'trail':
                $model = 'App\\Models\\TrailImage';
                break;
            default:
                $model = null;
        }

        if ($model) {
            $image = $model::where('id', $id)->first();
            if ($image) {
                $url = $this->imageService->getSignedUrlForImage($image, $size);
                if ($url) {
                    return redirect()->away($url);
                }
            }
            if ($size === 'thumbnail') {
                return redirect()->away(getenv('APP_URL') . '/images/trail-64x64.jpg');
            } else if ($size === 'medium') {
                return redirect()->away(getenv('APP_URL') . '/images/trail-720x720.jpg');
            }
            abort(404);
        } else {
            abort(404);
        }
    }

    public function getFeaturedImageUrl(Request $request, string $type, int $resourceId)
    {
        switch ($type) {
            case 'accommodation':
                $model = 'App\\Models\\AccommodationImage';
                break;
            case 'event':
                $model = 'App\\Models\\EventImage';
                break;
            case 'location':
            case 'restaurant':
                $model = 'App\\Models\\LocationImage';
                break;
            case 'trail':
                $model = 'App\\Models\\TrailImage';
                break;
            default:
                $model = null;
        }

        if ($model) {
            $image = $model::where($type . '_id', $resourceId)->first();
            if ($image) {
                return $this->getUrl($request, $type, $image->id);
            }
        }

        abort(404);
    }

    public function getMapTile(Request $request, string $mapType, $z, $x, $y, $ext)
    {
        return $this->imageService->getMapTile($mapType, $x, $y, $z, $ext);
    }
}
