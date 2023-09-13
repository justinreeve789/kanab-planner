<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Services\OCRService;
use Spatie\PdfToText\Pdf;

class EventService
{
    private $ocrService;

    public function __construct()
    {
        $this->ocrService = new OCRService(['conversionService' => $this]);
    }

    public function queryEvents($startDateTime, $endDateTime, $params = []) {
        $adventurousness = $params['adventurousness'];
        $solitude = $params['solitude'];
        $events = Event::between($startDateTime, $endDateTime);
        foreach ($events as $event) {
        }
    }
}