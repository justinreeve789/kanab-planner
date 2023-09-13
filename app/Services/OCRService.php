<?php

namespace App\Services;

use AhmadMayahi\Vision\Vision;
use App\Services\ConversionService;
use GuzzleHttp\Client;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRService
{
    const GOOGLE_CLOUD_VISION = 'googlecloudvision';
    const OCR_SPACE = 'ocrspace';
    const OCR_SPACE_API_URL = 'https://api.ocr.space/parse/image';
    const TESSERACT = 'tesseract';

    private $client;
    private $conversionService;

    public function __construct($services) {
        foreach ($services as $service => $obj) {
            $this->{$service} = $obj;
        }
    }

    public function getClient() {
        if (!isset($this->client)) {
            $this->client = new Client();
        }
        return $this->client;
    }

    public function scanPdfUrl(string $url): ?string
    {
        try {
            $temp = storage_path('temp/pdf_' . sha1(microtime()) . '.pdf');
            $pdf = file_get_contents($url);
            file_put_contents($temp, $pdf);
        } catch (\Exception $e) {
            // TODO: Error-handling.
            echo 'Could not download ' . $url . '
';
            return null;
        }

        $text = $this->convertPdfToText($temp);
        $this->conversionService->deleteFiles($temp);

        return $text;
    }

    public function scanImageUrl(string $url): ?string
    {
        $temp = storage_path('temp/img_' . sha1(microtime()));

        try {
            $image = file_get_contents($url);
            file_put_contents($temp, $image);
        } catch (\Exception $e) {
            // TODO: Error-handling.
            echo 'Could not download ' . $url . '
';
            return null;
        }

        try {
            $text = trim($this->convertImageToText($temp));
            if ($text == '') {
                $text = null;
            }
        } catch (\Exception $e) {
            // TODO: Error-handling.
            if (file_exists($temp)) {
                unlink($temp);
            }
            return null;
        }

        if (file_exists($temp)) {
            unlink($temp);
        }

        return $text;
    }

    public function convertImageToText($imagePath): ?string
    {
        if (env('OCR')) {
            $ocrOptions = explode(',', env('OCR'));
            if (!empty($ocrOptions)) {
                $ocr = $ocrOptions[0];
            }
        }
        switch ($ocr) {
            case self::GOOGLE_CLOUD_VISION:
                $text = $this->convertImageToTextWithGoogleCloudVision($imagePath);
                $this->conversionService->deleteFiles($imagePath);
                break;
            case self::OCR_SPACE:
            case self::TESSERACT:
            default:
                $text = $this->convertImageToTextWithTesseract($imagePath);
                $this->deleteFiles([$imagePath]);
                break;
        }
        return $text;
    }

    public function convertPdfToText($pdfPath): ?string
    {
        if (env('OCR')) {
            $ocrOptions = explode(',', env('OCR'));
            if (!empty($ocrOptions)) {
                $ocr = $ocrOptions[0];
            }
        }
        switch ($ocr) {
            case self::GOOGLE_CLOUD_VISION:
                $imagePath = $this->conversionService->convertPdfToImage($pdfPath);
                $text = $this->convertImageToTextWithGoogleCloudVision($imagePath);
                $this->conversionService->deleteFiles($imagePath);
                break;
            case self::OCR_SPACE:
                $text = $this->convertPdfToTextWithOCRSpace($pdfPath);
                break;
            case self::TESSERACT:
            default:
                $imagePath = $this->conversionService->convertPdfToImage($pdfPath);
                $text = $this->convertImageToTextWithTesseract($imagePath);
                $this->deleteFiles([$imagePath]);
                break;
        }
        return $text;
    }

    public function convertImageToTextWithGoogleCloudVision(string $imagePath): ?string
    {
        $config = (new \AhmadMayahi\Vision\Config())
            ->setCredentials('google-service-account.json')
            ->setTempDirPath(storage_path());
        $response = Vision::init($config)
            ->file($imagePath)
            ->imageTextDetection()
            ->plain();
        if ($response) {
            return $response->text;
        }
        return null;
    }

    public function convertPdfToTextWithGoogleCloudVision(string $pdfPath): ?string
    {
        return null;
    }

    public function convertPdfToTextWithOCRSpace(string $pdfUrl): ?string
    {
//        $imagePath = $this->conversionService->convertPdfToImage($pdfUrl, 'jpg');
//        $imageEncoded = 'data:image/jpg;base64,' . base64_encode(file_get_contents($imagePath));
        $pdfPath = $this->conversionService->downloadFile($pdfUrl);
        if (filesize($pdfPath) > 1000000) {
            $cmd = 'convert -density 150 "' . $pdfPath . '" -compress JPEG "' . $pdfPath . '.pdf"';
            exec($cmd);
            $this->conversionService->deleteFiles([$pdfPath]);
            $pdfPath .= '.pdf';
        }
        $pdfEncoded = 'data:application/pdf;base64,' . base64_encode(file_get_contents($pdfPath));
        $response = $this->getClient()->post(self::OCR_SPACE_API_URL, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'apikey' => env('OCRSPACE_API_KEY')],
            'form_params' => ['file' => $pdfEncoded, 'OCREngine' => 2, 'language' => 'eng', 'filetype' => 'PDF'],
        ]);
        $this->conversionService->deleteFiles([$pdfPath]);
        $results = json_decode($response->getBody()->getContents());

        $text = '';

        if (isset($results->ParsedResults)) {
            foreach ($results->ParsedResults as $parsedText) {
                $text .= $parsedText->ParsedText . '\n';
            }
        }
        return ($text !== '') ? $text : null;
    }

    public function convertImageToTextWithTesseract(string $imagePath): ?string
    {
        if (file_exists($imagePath)) {
            $ocr = new TesseractOCR();
            $ocr->image($imagePath);
            return $ocr->run();
        }
        return null;
    }
}
