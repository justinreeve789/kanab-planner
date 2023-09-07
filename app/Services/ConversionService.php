<?php

namespace App\Services;

use App\Models\Image;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ConversionService
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

    public function extractTextFromPdfUrl($pdfUrl): ?string
    {
        $text = null;

        try {
            $temp = storage_path('temp/pdf_' . sha1(microtime()) . '.pdf');
            $pdf = file_get_contents($pdfUrl);
            file_put_contents($temp, $pdf);
            $text = Pdf::getText($temp);
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }

        if (file_exists($temp)) {
            unlink($temp);
        }

        return $text;
    }

    public function deleteFiles(array $files)
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function extractTextFromHtmlImages(string $html): ?string
    {
        $imageUrls = $this->extractImagesFromHtml($html);
        foreach ($imageUrls as $imageUrl) {
            $text = $this->ocrService->scanImageUrl($imageUrl);
        }
    }

    public function extractImagesFromHtml(string $html): ?array
    {
//        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $match);
        preg_match_all('/<img.+src=[\'"](?P<src>.+?)[\'"].*>|background-image[ ]?:[ ]?url\([ ]?[\']?["]?(.*?\.(?:png|jpg|jpeg|gif|svg))/i', $html, $match);
        return $match['src'];
    }

    public function extractPDFsFromHtml(string $html): ?array
    {
        preg_match_all('/href=["\']?[^"\'>]+(?:\.pdf)["\']?/', $html, $match);
        print_r($match);
        return [];
    }
}
