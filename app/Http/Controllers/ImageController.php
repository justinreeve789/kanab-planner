<?php

namespace App\Http\Controllers;

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
        $response = $this->imageService->search($query);
        echo '<pre>'; print_r($response); echo '</pre>';
    }

    public function getUrl(Request $request, string $type, int $id, bool $thumbnail = false) {
        $thumbnail = (bool)$request->get('thumb') || (bool)$request->get('thumbnail');

        switch ($type) {
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
                $url = $this->imageService->getSignedUrlForImage($image, $thumbnail);
                if ($url) {
                    return redirect()->away($url);
                }
            }
        } else {
            abort(404);
        }
    }

    public function getFeaturedImageUrl(Request $request, string $type, int $resourceId)
    {
        switch ($type) {
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
}
