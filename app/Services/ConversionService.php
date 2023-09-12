<?php

namespace App\Services;

use App\Models\Image;
use App\Services\OCRService;
use Spatie\PdfToText\Pdf;

class ConversionService
{
    private $ocrService;

    public function __construct()
    {
        $this->ocrService = new OCRService(['conversionService' => $this]);
    }

    public function isUrl($str)
    {
        return (filter_var($str, FILTER_VALIDATE_URL) !== false);
    }

    public function downloadFile($url)
    {
//        $headers = get_headers($url, 1);
//        $contentType = $headers['Content-Type'];
        $temp = storage_path('temp/file_' . sha1(microtime()));
        $file = file_get_contents($url);
        file_put_contents($temp, $file  );
        return $temp;
    }

    public function convertPdfToImage($pdfPath, $fileType = 'png'): ?string
    {
        if ($this->isUrl($pdfPath)) {
            $pdfPath = $this->downloadFile($pdfPath);
        }

        $temp = storage_path('temp/' . $fileType . '_' . sha1(microtime()) . '.' . $fileType);
        try {
            $cmd = 'convert "' . $pdfPath . '" -append "' . $temp . '"';
            @exec($cmd);
        } catch (\Exception $e) {
            // TODO: Error-handling.
        }
        if (file_exists($temp)) {
            return $temp;
        }
        return null;
    }

    public function convertPdfToText($pdfPath): ?string
    {
        return $this->ocrService->convertPdfToText($pdfPath);
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

    public function deleteFiles($files)
    {
        if (!is_array($files)) {
            $files = [$files];
        }
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

    public function extractUrlsFromHtml(string $html): ?array
    {
        preg_match_all('/(http|ftp|https):\/\/([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/', $html, $match);
        return $match[0];
    }
}
