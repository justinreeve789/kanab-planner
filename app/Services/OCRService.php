<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRService
{
    public function convertPdfToImage($pdfPath): ?string
    {
        $temp = storage_path('temp/png_' . sha1(microtime()) . '.png');
        try {
            $im = new \Imagick($pdfPath);
            $im->writeImage($temp);
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }
        if (file_exists($temp)) {
            return $temp;
        }
        return null;
    }

    public function scanPdfUrl(string $url): ?string
    {
        $temp = storage_path('temp/pdf_' . sha1(microtime()) . '.pdf');
        $pdfPath = file_get_contents($url);
        file_put_contents($temp, $pdfPath);
        try {
            $imagePath = $this->convertPdfToImage($pdfPath);
            $text = $this->convertImageToText($imagePath);
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }
        if (isset($imagePath)) {
            unlink($imagePath);
        }
        unlink($pdfPath);
        return $text;
    }

    public function scanImageUrl(string $url): ?string
    {
        $text = null;
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

    public function convertImageToText(string $imagePath): ?string
    {
        if (file_exists($imagePath)) {
            $ocr = new TesseractOCR();
            $ocr->image($imagePath);
            return $ocr->run();
        }
        return null;
    }
}
