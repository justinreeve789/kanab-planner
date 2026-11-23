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

    public function downloadFile($url): ?string
    {
//        $headers = get_headers($url, 1);
//        $contentType = $headers['Content-Type'];
        $temp = storage_path('temp/file_' . sha1(microtime()));
        try {
            $file = file_get_contents($url);
        } catch (\Throwable $e) {
            return null;
        }
        file_put_contents($temp, $file);
        return $temp;
    }

    public function convertImageToText($imagePath): ?string
    {
        $text = null;
        if ($this->isUrl($imagePath)) {
            $imagePath = $this->downloadFile($imagePath);
        }
        if ($imagePath) {
            $text = $this->ocrService->convertImageToText($imagePath);
        }
        $this->deleteFiles($imagePath);
        return $text;
    }

    public function convertPdfToImage($pdfPath, $multipleFiles = false, $fileType = 'png')
    {
        if ($this->isUrl($pdfPath)) {
            $pdfPath = $this->downloadFile($pdfPath);
            rename($pdfPath, $pdfPath . '.pdf');
        }

        if ($multipleFiles === true) {
            $temp = storage_path('temp/' . $fileType . '_' . sha1(microtime()));
            try {
                $cmd = 'convert -density 150 "' . $pdfPath . '" -quality 90 "' . $temp . '-%02d.' . $fileType . '"';
                @exec($cmd);
                $files = glob($temp . '*');
                if ($files !== false) {
                    return $files;
                }
            } catch (\Exception $e) {
                // TODO: Error-handling.
            }
        } else {
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
        }
        return null;
    }

    public function convertPdfToText($pdfPath): ?string
    {
        $text = null;
        if ($this->isUrl($pdfPath)) {
            $pdfPath = $this->downloadFile($pdfPath);
        }
        if ($pdfPath) {
            $text = $this->ocrService->convertPdfToText($pdfPath);
        }
        $this->deleteFiles($pdfPath);
        return $text;
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

    /**
     * OCR text of every image in the HTML, joined by blank lines.
     */
    public function extractTextFromHtmlImages(string $html): ?string
    {
        $texts = [];
        foreach ($this->extractImagesFromHtml($html) as $imageUrl) {
            $text = $this->convertImageToText($imageUrl);
            if ($text) {
                $texts[] = trim($text);
            }
        }
        return $texts ? implode("\n\n", $texts) : null;
    }

    /**
     * Image URLs in document order: <img src> and CSS background-image url(...).
     */
    public function extractImagesFromHtml(string $html): ?array
    {
        preg_match_all(
            '/<img\b[^>]*?\bsrc\s*=\s*([\'"])(?P<img>.+?)\1|background-image\s*:\s*url\(\s*([\'"]?)(?P<bg>[^\'")]+?\.(?:png|jpe?g|gif|svg|webp))\3\s*\)/i',
            $html,
            $matches,
            PREG_SET_ORDER
        );
        return array_map(fn($m) => ($m['img'] ?? '') !== '' ? $m['img'] : $m['bg'], $matches);
    }

    /**
     * href values that point at a .pdf, in document order.
     */
    public function extractPDFsFromHtml(string $html): ?array
    {
        preg_match_all('/href\s*=\s*([\'"]?)([^\'"\s>]+\.pdf(?:[?#][^\'"\s>]*)?)\1/i', $html, $matches);
        return $matches[2];
    }

    public function extractUrlsFromHtml(string $html): ?array
    {
        preg_match_all('/(http|ftp|https):\/\/([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/', $html, $match);
        return $match[0];
    }
}
