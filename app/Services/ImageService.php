<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use DuckDuckGoImages\Client;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToRetrieveMetadata;
use Throwable;

class ImageService
{
    const CALTOPO_GEOLOGY = 'caltopo_geology';
    const CALTOPO_GEOLOGY_PATH = 'visitkanab/maptiles/caltopo/geology';
    const CALTOPO_GEOLOGY_URL = 'https://caltopo.com/tile/geology';
    const CALTOPO_MB_HYBRID = 'caltopo_mb_hybrid';
    const CALTOPO_MB_HYBRID_PATH = 'visitkanab/maptiles/caltopo/mb_hybrid';
    const CALTOPO_MB_HYBRID_URL = 'https://caltopo.com/tile/mb_hybrid';
    const OPENSTREETMAP = 'openstreetmap';
    const OPENSTREETMAP_URL = 'https://a.tile.openstreetmap.org';
    const OPENTOPOMAP = 'opentopomap';
    const OPENTOPOMAP_PATH = 'visitkanab/maptiles/opentopomap';
    const OPENTOPOMAP_URL = 'https://a.tile.opentopomap.org';
    const STADIA_OUTDOORS = 'stadia_outdoors';
    const STADIA_OUTDOORS_PATH = 'visitkanab/maptiles/stadia/outdoors';
    const STADIA_OUTDOORS_URL = 'https://tiles.stadiamaps.com/tiles/outdoors';
    const STADIA_STAMEN_TERRAIN = 'stadia_stamen_terrain';
    const STADIA_STAMEN_TERRAIN_PATH = 'visitkanab/maptiles/stadia/stamem-terrain';
    const STADIA_STAMEN_TERRAIN_URL = 'https://tiles.stadiamaps.com/tiles/stamen_terrain';
    const THUNDERFOREST_CYCLE = 'thunderforest_cycle';
    const THUNDERFOREST_CYCLE_PATH = 'visitkanab/maptiles/thunderforest/cycle';
    const THUNDERFOREST_CYCLE_URL = 'https://tile.thunderforest.com/cycle';
    const THUNDERFOREST_OUTDOORS = 'thunderforest_outdoors';
    const THUNDERFOREST_OUTDOORS_PATH = 'visitkanab/maptiles/thunderforest/outdoors';
    const THUNDERFOREST_OUTDOORS_URL = 'https://tile.thunderforest.com/outdoors';

    public $conversionService;
    public $s3;
    private $client;
    private $images = [];
    private $s3Client;

    public function __construct() {
        $this->client = new Client();
        $this->conversionService = new ConversionService();
        $this->s3 = Storage::disk('do');
    }

    public function getS3Client()
    {
        if (!isset($this->s3Client)) {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region'  => getenv('DO_DEFAULT_REGION'),
                'endpoint' => getenv('DO_ENDPOINT'),
                'use_path_style_endpoint' => false, // Configures to use subdomain/virtual calling format.
                'credentials' => [
                    'key'    => getenv('DO_ACCESS_KEY_ID'),
                    'secret' => getenv('DO_SECRET_ACCESS_KEY'),
                ],
            ]);
        }
        return $this->s3Client;
    }

    public function search($query) {
        return $this->client->getImages($query);
    }

    public function getSignedUrlForImage($obj, $size = null): ?string
    {
        switch ($size) {
            case 'medium':
                $url = $obj->medium_local_url ?? $obj->local_url;
                break;
            case 'thumbnail':
                $url = $obj->thumbnail_local_url;
                break;
            default:
                $url = $obj->local_url;
        }

        if (!$url) {
            return null;
        }

        switch (get_class($obj)) {
            case 'App\\Models\\AccommodationImage':
            case 'App\\Models\\EventImage':
            case 'App\\Models\\LocationImage':
            case 'App\\Models\\TrailImage':
                $cmd = $this->getS3Client()->getCommand('GetObject', [
                    'Bucket' => getenv('DO_BUCKET'),
                    'Key' => $url,
                ]);

                $request = $this->getS3Client()->createPresignedRequest($cmd, '+' . getenv('DO_EXPIRE_SIGNED_URLS') . ' minutes');
                return (string) $request->getUri();
            default:
        }
        return null;
    }

    public function storeImage($url) {
        $newFileName = storage_path('images/' . pathinfo(md5($url), PATHINFO_FILENAME));
        echo 'Searching ' . $newFileName . '
';
        if (!file_exists($newFileName . '.jpg') && !file_exists($newFileName . '.png')) {
            echo 'Downloading ' . $url . '
';
            $tempFile = $this->conversionService->downloadFile($url);
            if (!$tempFile) {
                return false;
            }

            // Hash it based on the URL, and rename it with the correct file extension.
            $newFileName = storage_path('images/' . str_replace('file_', '', pathinfo(md5($url), PATHINFO_FILENAME)) . $this->getFileExtension($tempFile));
            rename($tempFile, $newFileName);
        } else {
            $newFileName = $this->resolveFile($newFileName);
            echo 'Found ' . $newFileName . '
';
        }

        // Store to the cloud.
        $filePath = getenv('DO_FOLDER') . '/' . pathinfo($newFileName, PATHINFO_BASENAME);
        echo 'Uploading to ' . $filePath . '
';
        if (!$this->s3->put($filePath, file_get_contents($newFileName))) {
            return null;
        }

        return ['path' => $filePath, 'size' => filesize($newFileName)];
    }

    /**
     * Abstract storeImage() and use it instead of this function, so we're not duplicating code.
     */
    public function putImageInStorage($file, $filename) {
        $filePath = getenv('DO_FOLDER') . '/' . $filename;
        echo 'Uploading to ' . $filePath . '
';
        if (!$this->s3->put($filePath, file_get_contents($file))) {
            return null;
        }

        return ['path' => $filePath, 'size' => filesize($file)];
    }

    public function getFileExtension($file) {
        $mime = mime_content_type($file);
        switch ($mime) {
            case 'image/png':
                return '.png';
            case 'image/gif':
                return '.gif';
            case 'image/bmp':
                return '.bmp';
            case 'image/jpeg':
            default:
                return '.jpg';
        }
    }

    public function getLatLng($file): ?array {
        if ($this->conversionService->isUrl($file)) {
            $filename = $this->conversionService->downloadFile($file);
        } else {
            $filename = $file;
        }

        try {
            $exif = exif_read_data($filename, 0, true);
        } catch (\Throwable $e) {
            return null;
        }

        if ($this->conversionService->isUrl($file)) {
            $this->conversionService->deleteFiles($filename);
        }

        if (!isset($exif['GPS']) || !isset($exif['GPS']['GPSLatitude'])) {
            return null;
        }

        $info = $exif['GPS'];

        $lat_degrees_fraction = explode('/', $info['GPSLatitude'][0]);
        $lat_minutes_fraction = explode('/', $info['GPSLatitude'][1]);
        $lat_seconds_fraction = explode('/', $info['GPSLatitude'][2]);
        $lng_degrees_fraction = explode('/', $info['GPSLongitude'][0]);
        $lng_minutes_fraction = explode('/', $info['GPSLongitude'][1]);
        $lng_seconds_fraction = explode('/', $info['GPSLongitude'][2]);

        $lat_degrees = floatval($lat_degrees_fraction[0]) / floatval($lat_degrees_fraction[1]);
        $lat_minutes = floatval($lat_minutes_fraction[0]) / floatval($lat_minutes_fraction[1]);
        $lat_seconds = floatval($lat_seconds_fraction[0]) / floatval($lat_seconds_fraction[1]);
        $lng_degrees = floatval($lng_degrees_fraction[0]) / floatval($lng_degrees_fraction[1]);
        $lng_minutes = floatval($lng_minutes_fraction[0]) / floatval($lng_minutes_fraction[1]);
        $lng_seconds = floatval($lng_seconds_fraction[0]) / floatval($lng_seconds_fraction[1]);

        $lat = floatval($lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600));
        $lng = floatval($lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600));

        if ($lat && $lng) {
            return ['latitude' => $lat, 'longitude' => $lng];
        }

        return null;
    }

    public function getMapTile(string $mapType, int $x, int $y, int $z, string $ext = 'png', $cache = true) {
        $defaultMapType = self::OPENSTREETMAP;
        $convertPngToJpg = [
            self::THUNDERFOREST_CYCLE,
            self::THUNDERFOREST_OUTDOORS,
        ];
        switch ($mapType) {
            case self::CALTOPO_GEOLOGY:
                $path = self::CALTOPO_GEOLOGY_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::CALTOPO_GEOLOGY_URL . "/{$z}/{$x}/{$y}.{$ext}";
                break;
            case self::CALTOPO_MB_HYBRID:
                $path = self::CALTOPO_MB_HYBRID_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::CALTOPO_MB_HYBRID_URL . "/{$z}/{$x}/{$y}.{$ext}?ctdarkmode=false";
                break;
            case self::OPENTOPOMAP:
                $path = self::OPENTOPOMAP_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::OPENTOPOMAP_URL . "/{$z}/{$x}/{$y}.{$ext}";
                break;
            case self::STADIA_OUTDOORS:
                $path = self::STADIA_OUTDOORS_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::STADIA_OUTDOORS_URL . "/{$z}/{$x}/{$y}.{$ext}?1api_key=" . Config::get('services.stadia.key');
                break;
            case self::STADIA_STAMEN_TERRAIN:
                $path = self::STADIA_STAMEN_TERRAIN_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::STADIA_STAMEN_TERRAIN_URL . "/{$z}/{$x}/{$y}.{$ext}?api_key=" . Config::get('services.stadia.key');
                break;
            case self::THUNDERFOREST_CYCLE:
                $path = self::THUNDERFOREST_CYCLE_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::THUNDERFOREST_CYCLE_URL . "/{$z}/{$x}/{$y}.{$ext}?apikey=" . Config::get('services.thunderforest.key');
                break;
            case self::THUNDERFOREST_OUTDOORS:
                $path = self::THUNDERFOREST_OUTDOORS_PATH . "/{$z}/{$x}/{$y}.{$ext}";
                $url = self::THUNDERFOREST_OUTDOORS_URL . "/{$z}/{$x}/{$y}.{$ext}?apikey=" . Config::get('services.thunderforest.key');
                break;
            case self::OPENSTREETMAP:
                // We won't ever store OpenStreetMap files.
                $cache = false;
                $url = self::OPENSTREETMAP_URL . "/{$z}/{$x}/{$y}.{$ext}";
            default:
                break;
        }

        if ($cache === false) {
            return redirect($url);
        }

        // Check if the tile exists in S3.
        try {
            if ($this->s3->size($path) > 200) {
                $tile = $this->s3->get($path);
                return response($tile, 200, [
                    'Content-Type' => $this->getMimeTypeForExtension($ext),
                    'Cache-Control' => 'public, max-age=86400',
                    'Expires' => now()->addSeconds(86400)->toRfc1123String(),
                ]);
            }
            throw new Exception();
        } catch (Throwable $e) {
            if ($ext === 'jpg') {
                if (in_array($mapType, $convertPngToJpg)) {
                    $pngPath = str_replace('.' . $ext, '.png', $path);
                    if ($this->s3->exists($pngPath)) {
                        $tempJpg = storage_path('temp/' . uniqid('tile', true) . '.jpg');
                        $tempPng = storage_path('temp/' . uniqid('tile', true) . '.png');
                        $tile = $this->s3->get($pngPath);
                        file_put_contents($tempPng, $tile);
                        $image = imagecreatefrompng($tempPng);
                        unlink($tempPng);
                        imagejpeg($image, $tempJpg, 75);
                        if (file_exists($tempJpg)) {
                            $this->s3->put($path, file_get_contents($tempJpg));
                            unlink($tempJpg);
                            if ($this->s3->exists($path)) {
                                $tile = $this->s3->get($path);
                                Log::info('Converted: ' . $pngPath . ' to ' . $path);
                                return response($tile, 200, [
                                    'Content-Type' => $this->getMimeTypeForExtension($ext),
                                    'Cache-Control' => 'public, max-age=86400',
                                    'Expires' => now()->addSeconds(86400)->toRfc1123String(),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Tile does not exist, so we'll fetch it from the provider.
        getTile:
        try {
            $tileData = Http::get($url);
        } catch (\GuzzleHttp\Exception\ConnectException|\Illuminate\Http\Client\ConnectionException $e) {
            goto getTile;
        }

        if ($tileData->successful()) {
            $this->s3->put($path, $tileData->body());
            Log::info($url);
            return response($tileData->body(), 200, [
                'Content-Type' => $this->getMimeTypeForExtension($ext),
                'Cache-Control' => 'public, max-age=86400',
                'Expires' => now()->addSeconds(86400)->toRfc1123String(),
            ]);
        } else {
            // Use the default if the tile could not be fetched.
            return $this->getMapTile($defaultMapType, $x, $y, $z, $ext);
        }
    }

    public function getMimeTypeForExtension($ext): string {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];
        return $mimeTypes[$ext] ?? '';
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

    /**
     * Convert latitude and longitude to map tile coordinates for a given zoom level.
     *
     * @param $lat
     * @param $lng
     * @param $zoom
     * @return array
     */
    function latLngToMapTile($lat, $lng, $zoom) {
        $latRad = deg2rad($lat);
        $x = floor((($lng + 180) / 360) * pow(2, $zoom));
        $y = floor((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * pow(2, $zoom));
        return ['x' => $x, 'y' => $y , 'z' => $zoom];
    }

    public function resizeImage($file, int $newWidth, int $newHeight) {
        if ($this->conversionService->isUrl($file)) {
            echo 'Downloading ' . $file . '
';
            $filename = $this->conversionService->downloadFile($file);
        } else {
            $filename = $file;
        }

        echo 'Processing ' . $filename . '
';
        // Create a new image from the original.
        try {
            $originalImage = \imagecreatefromstring(file_get_contents($filename));
        } catch (\Throwable $e) {
            echo 'Skipping ' . $filename . '
';
            return null;
        }

        // Get the original image's width and height.
        $originalWidth = \imagesx($originalImage);
        $originalHeight = \imagesy($originalImage);

        // Calculate the aspect ratio of the original image.
        $aspectRatio = $originalWidth / $originalHeight;

        // Calculate the crop dimensions to maintain aspect ratio.
        if ($aspectRatio > 1) {
            // Landscape orientation (wider than tall).
            $cropWidth = $originalHeight * $aspectRatio;
            $cropHeight = $originalHeight;
        } else {
            // Portrait orientation (taller than wide).
            $cropWidth = $originalWidth;
            $cropHeight = $originalWidth / $aspectRatio;
        }

        // Calculate the crop position to center the image.
        $cropX = ($originalWidth - $cropWidth) / 2;
        $cropY = ($originalHeight - $cropHeight) / 2;

        // Create a new blank image with the desired dimensions.
        $newImage = \imagecreatetruecolor($newWidth, $newHeight);

        // Crop and resize the original image to fit the new dimensions.
        \imagecopyresampled($newImage, $originalImage, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $cropWidth, $cropHeight);

        // Save or display the cropped and resized image (here, we're saving it as a new file).
        $resizedImagePath = storage_path('temp/' . md5($filename) . '.jpg');
        \imagejpeg($newImage, $resizedImagePath);

        // Clean up resources.
        \imagedestroy($originalImage);
        \imagedestroy($newImage);

        if ($this->conversionService->isUrl($file)) {
            $this->conversionService->deleteFiles($filename);
        }

        return $resizedImagePath;
    }

    private function getGps($exifCoord, $hemi) {

        $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

    }

    private function gps2Num($coordPart) {

        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }

    private function resolveFile($name) {
        // reads informations over the path
        $info = pathinfo($name);
        if (!empty($info['extension'])) {
            // if the file already contains an extension returns it
            return $name;
        }
        $filename = $info['filename'];
        $len = strlen($filename);
        // open the folder
        $dh = opendir($info['dirname']);
        if (!$dh) {
            return false;
        }
        // scan each file in the folder
        while (($file = readdir($dh)) !== false) {
            if (strncmp($file, $filename, $len) === 0) {
                if (strlen($name) > $len) {
                    // if name contains a directory part
                    $name = substr($name, 0, strlen($name) - $len) . $file;
                } else {
                    // if the name is at the path root
                    $name = $file;
                }
                closedir($dh);
                return $name;
            }
        }
        // file not found
        closedir($dh);
        return false;
    }
}
