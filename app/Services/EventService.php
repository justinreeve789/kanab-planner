<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Services\OCRService;
use App\Services\OpenAIService;
use Spatie\PdfToText\Pdf;

class EventService
{
    private $openAIService;

    public function __construct()
    {
        $this->openAIService = new OpenAIService();
    }

    private function getOpenAIService()
    {
        return $this->openAIService;
    }

    public function queryEvents($startDateTime, $endDateTime, $params = []) {
        $adventurousness = $params['adventurousness'];
        $solitude = $params['solitude'];
        $events = Event::betweenDates($startDateTime, $endDateTime);
        foreach ($events as $event) {
        }
    }

    public function calculateResourceData(Event $event): ?array
    {
        $prompt = "Direct answers only. In three numbers from 0.0 to 1.0, make assumptions where necessary. Adventurousness: Calculate how unique, adventurousness, and out of the average person's comfort zone the event is. Socialization: Calculate how likely I'll have to interact with strangers or be around large crowds at the event. Strenuousness: Calculate how physically exerting the event is. Here's the event: ";
        $prompt .= $event->name . " " . $event->description;
//        \Log::info(print_r($prompt, true));
        $response = $this->getOpenAIService()->query($prompt, 50);
        if (preg_match_all('/\d+(\.\d+)?/', $response, $matches)) {
            $nums = array_slice($matches[0], 0, 3);
            if (count($nums) === 3) {
                return [
                    'adventurousness' => floatval($nums[0]),
                    'solitariness' => 1.0 - floatval($nums[1]),
                    'strenuousness' => floatval($nums[2]),
                ];
            }
        }
        \Log::error('Resource data not calculated for: ' . json_encode($event->toArray()) . ' - ' . json_encode($response));
        return null;
    }

    private function calculateAdventurousness(Event $event): ?float
    {
        if ($event->description) {
            $prompt = "No summary, in three numbers from 0.0 to 1.0, make assumptions where necessary. Calculate how unique, adventurousness, and out of the average person's comfort zone this event is: ";
            $prompt .= $event->name . " " . $event->description;
        }

    }

    private function calculateSolitariness($eventId)
    {
        $prompt = "No summary, in one number from 0.0 to 1.0, make assumptions where necessary. Calculate how likely I'll have to interact with strangers or be around large crowds at this event: ";
    }

    private function calculateStrenuousness($eventId)
    {
        $prompt = "No summary, in one number from 0.0 to 1.0, make assumptions where necessary. Calculate how physically exerting this event is: ";
    }
}