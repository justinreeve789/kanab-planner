<?php
/**
 * ddg-image-search - search for images on DuckDuckGo and download them.
 *
 * Copyright (C) 2019 Wolfgang Frisch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Services;

use DuckDuckGoImages\Client;

class ImageService
{
    // We use GD for image manipulation instead of PIL
    // Make sure GD is enabled in your PHP configuration.

    private $client;
    private $images = [];

    public function __construct() {
        $this->client = new Client();
    }

    public function search($query) {
        return $this->client->getImages($query);
    }

    public function getPhotos($query) {
        $response = $this->client->getImages($query);
        $photos = [];
        foreach ($response['results'] as $index => $photo) {
            if ($index > 10) {
                break;
            }
            $photos[] = (object) [
                'id' => $photo['image_token'],
                'url' => $photo['image'],
            ];
        }
        $this->images[$query] = $photos;
        return $photos;
    }
}