<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    private string $sourceUrl;
    private ?string $storagePath;
    private ?string $text;

    public function __construct($sourceUrl, $storagePath = null, $text = null)
    {
        $this->setSourceUrl($sourceUrl);
        $this->setStoragePath($storagePath);
        $this->setText($text);
    }

    /**
     * @return string
     */
    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    /**
     * @param string $sourceUrl
     */
    public function setSourceUrl(string $sourceUrl): void
    {
        $this->sourceUrl = $sourceUrl;
    }

    /**
     * @return string
     */
    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    /**
     * @param string $storagePath
     */
    public function setStoragePath(?string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }
}