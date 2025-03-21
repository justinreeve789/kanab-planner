<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\ResourceText;
use App\Models\Trail;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public function query($prompt, $maxTokens = 500) {
        $result = OpenAI::chat()->create([
            'max_tokens' => $maxTokens,
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'user',
                'content' => $prompt,
            ]],
        ]);
        return $result['choices'][0]['message']['content']; // an open-source, widely-used, server-side scripting language.
    }

    public function cleanTextWithWGPT(string $text): ?string {
        $cmd = 'wgpt "Clean up, fix misspellings and grammar, and format the following text for a travel web site about Kanab and southern Utah, with information about hiking trails in the area: ';
        $cmd .= str_replace('"', '', $text) . '"';
        return shell_exec($cmd);
    }

    public function identifyActivityTypeWithOpenAI(string $text): ?string {
        $activityTypes = implode(', ', ActivityType::get()->pluck('name')->toArray());
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a helpful assistant categorizing events and activities for a travel web site about southern Utah and northern Arizona.',
            ],[
                'role' => 'user',
                'content' => 'In one word, what type of activity is this from the following options: {' . $activityTypes . '}: ' . $text,
            ]],
            'max_tokens' => 2000,
        ]);
        return strtolower($result['choices'][0]['message']['content']);
    }

    public function createShortDescriptionForResourceWithWithOpenAI(string $text): ?string {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a helpful assistant summarizing hiking trails for a travel web site about southern Utah and northern Arizona.',
            ],[
                'role' => 'user',
                'content' => 'Based on this trail description, using an active voice (NOT passive voice), without using the word "hikers", in one complete sentence, write this as an itinerary line item and explain what the hikers will see: ' . $text,
            ]],
            'max_tokens' => 500,
        ]);
        return $result['choices'][0]['message']['content'];
    }

    public function rewriteTextWithOpenAI(string $text): ?string {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a helpful assistant rewriting and re-summarizing text for a travel web site about southern Utah and northern Arizona.',
            ],[
                'role' => 'user',
                'content' => 'Summarize, rewrite, fix misspellings, remove the title, and format as a JSON string containing five fields: new_text_plain, new_text_html, summarized_text_plain, summarized_text_html, additional_info: ' . $text,
            ]],
            'max_tokens' => 2000,
        ]);
        return $result['choices'][0]['message']['content'];
    }

    public function cleanTextWithOpenAI(string $text): ?string {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-4.0-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a helpful assistant transcribing scanned text and re-summarizing it for a travel web site about southern Utah and northern Arizona.',
            ],[
                'role' => 'user',
                'content' => 'Summarize, fix misspellings, remove the title, expand on the text, and format as a JSON string containing trail_name, distance, best_season, difficulty, elevation, latitude, longitude, accessibility, pets_allowed, reservation_required, average_hours_to_hike, vehicle, getting_there, description, description_html, and additional_info: ' . $text,
            ]],
            'max_tokens' => 2000,
        ]);
        return $result['choices'][0]['message']['content'];
    }

    public function cleanText(string $text): ?string {
        return $this->cleanTextWithOpenAI($text);
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
