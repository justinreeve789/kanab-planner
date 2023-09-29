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

    public function getSignedUrlForImage($obj): ?string
    {
        switch (get_class($obj)) {
            case 'App\\Models\\EventImage':
            case 'App\\Models\\LocationImage':
            case 'App\\Models\\TrailImage':
                $cmd = $this->getS3Client()->getCommand('GetObject', [
                    'Bucket' => getenv('DO_BUCKET'),
                    'Key' => $obj->local_url,
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