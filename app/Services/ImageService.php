<?php

namespace App\Services;

use Aws\S3\S3Client;
use DuckDuckGoImages\Client;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    // We use GD for image manipulation instead of PIL
    // Make sure GD is enabled in your PHP configuration.

    private $client;
    private $conversionService;
    private $images = [];
    private $s3;
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

    public function getSignedUrlForImage($obj, $thumbnail = false): ?string
    {
        switch (get_class($obj)) {
            case 'App\\Models\\EventImage':
            case 'App\\Models\\LocationImage':
            case 'App\\Models\\TrailImage':
                $cmd = $this->getS3Client()->getCommand('GetObject', [
                    'Bucket' => getenv('DO_BUCKET'),
                    'Key' => ($thumbnail === false) ? $obj->local_url : $obj->thumbnail_local_url,
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
    public function storeThumbnail($file, $filename) {
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

    public function resizeImage($file) {
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
            $originalImage = \imagecreatefromjpeg($filename);
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

        // Define the desired width and height for the cropped image (64x64).
        $newWidth = 64;
        $newHeight = 64;

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