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

    public function getUrl(Request $request, string $type, int $id) {
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
                $url = $this->imageService->getSignedUrlForImage($image);
                if ($url) {
                    return redirect()->away($url);
                }
            }
        } else {
            abort(404);
        }
    }
}
