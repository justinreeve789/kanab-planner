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
        $result = OpenAI::chat()->create([
            'max_tokens' => 500,
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'user',
                'content' => $prompt,
            ]],
        ]);
        return $result['choices'][0]['text']; // an open-source, widely-used, server-side scripting language.
    }

    public function cleanTextWithWGPT(string $text): ?string {
        $cmd = 'wgpt "Clean up, fix misspellings and grammar, and format the following text for a travel web site about Kanab and southern Utah, with information about hiking trails in the area: ';
        $cmd .= str_replace('"', '', $text) . '"';
        return shell_exec($cmd);
    }

    public function cleanTextWithOpenAI(string $text): ?string {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'user',
                'content' => 'Clean up and rewrite the following text so it\'s properly formatted and legible: ' . $text,
            ]],
            'max_tokens' => 1000,
        ]);
        print_r($result);
    }

    public function cleanText(string $text): ?string {
        return $this->cleanTextWithWGPT($text);
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