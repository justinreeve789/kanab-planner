<?php

namespace App\Http\Controllers;

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
}
