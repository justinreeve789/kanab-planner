<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRService
{
    public function convertPdfToImage($pdfPath): ?string
    {
        $temp = storage_path('temp/png_' . sha1(microtime()) . '.png');
        try {
            $im = new \Imagick();
            $im->readImage($pdfPath);
            $im->writeImage($temp);
//            $cmd = 'convert -density 96 -quality 85 "' . $pdfPath . '" "' . $temp . '"';
//            exec($cmd);
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
        $text = null;

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

        try {
            $imagePath = $this->convertPdfToImage($temp);
            if ($imagePath) {
                $text = $this->convertImageToText($imagePath);
            }
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }

        if (file_exists($imagePath)) {
//            unlink($imagePath);
        }

        if (file_exists($temp)) {
            unlink($temp);
        }

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
