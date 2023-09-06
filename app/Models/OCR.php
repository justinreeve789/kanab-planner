<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OCR extends Model
{
    use HasFactory;

    public function convertPdfToImage($pdfPath): ?string
    {
        $temp = storage_path('ocr/png_' . sha1(microtime()) . '.png');
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
        $temp = storage_path('ocr/pdf_' . sha1(microtime()) . '.pdf');
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
        $temp = storage_path('ocr/image_' . sha1(microtime()));
        $imagePath = file_get_contents($url);
        file_put_contents($temp, $imagePath);
        try {
            $text = $this->convertImageToText($imagePath);
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }
        unlink($imagePath);
        return $text;
    }

    private function convertImageToText(string $imagePath): ?string
    {
        if (file_exists($imagePath)) {
            $tesseract = new TesseractOCR(asset($imagePath));
            return $tesseract->recognize();
        }
        return null;
    }
}
