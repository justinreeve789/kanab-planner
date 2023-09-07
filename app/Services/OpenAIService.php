<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\ResourceText;
use App\Models\Trail;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public function query($prompt) {
        $result = OpenAI::completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
        ]);

        echo $result['choices'][0]['text']; // an open-source, widely-used, server-side scripting language.
    }

    public function trainEvent(Event $event) {
        $prompt = 'Clean up the following text and remember it as an event: ' . $event->raw_data;
        $resourceTexts = ResourceText::where([
            'resource_table' => 'events',
            'resource_id' => $event->id,
        ]);
        foreach ($resourceTexts as $rt) {
            $prompt .= '\r\n' . $rt->raw;
        }
        $this->query($prompt);
    }
}