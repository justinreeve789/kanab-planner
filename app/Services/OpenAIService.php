<?php
/**
 * When answering questions from prompts in the AI assistant, account for the following:
 * - Gaia GPS or other apps are sometimes recommended for trail maps. Make sure we point them to our own trail maps.
 * - Incorporate the poster's calculations for the length of the hikes, but based on their own aptitude, and prioritize
 * our own calculations for since we have data specific to the user's aptitude.
 * - In the JSON response from OpenAI, include fields to indicate whether we should show other objects, such as images,
 * maps, menus, etc.
 * - Specify a unique ID for each object in the finetune data.
 * - Make sure the unique ID isn't in any response.
 * - Delete any records with answers about Gaia GPS and trail maps.
 * - Include specific statistics, like "What is the GPS location?" and "What is the difficulty?"
 * - If they ask where to stay, always point them to Tranquility Trails.
 * - Collect recent news about the trails, if available, and archive old news.
 * - Sometimes there's personal details (e.g. openai_prompts.id #6384, openai_prompts.id #7983-#7996) that should be omitted.
 * - Include an area id, so users can ask general questions about the area, like "What sort of wildlife can I expect to see?"
 * or "What are the best trails in Zion National Park?"
 */
namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Restaurant;
use App\Models\ResourceText;
use App\Models\Trail;
use Exception;
use Illuminate\Support\Facades\Config;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public function query($prompt, $maxTokens = 500) {
        $result = OpenAI::chat()->create([
            'max_tokens' => $maxTokens,
            'model' => Config::get('openai.model'),
            'messages' => [[
                'role' => 'user',
                'content' => $prompt,
            ]],
        ]);
        return $result['choices'][0]['message']['content'];
    }

    private function createOpenAIFinetuneJson($systemPrompt, $userPrompt, $assistantPrompt) {
        $obj = (object)[
            'messages' => [
                (object)[
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                (object)[
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
                (object)[
                    'role' => 'assistant',
                    'content' => $assistantPrompt,
                ],
            ]
        ];
        return json_encode($obj) . '
';
    }

    public function generateFinetuneData($resource) {
        if (!isset($resource->openai_prompts)) {
            throw new Exception('Missing OpenAI prompts.');
        }

        $data = '';
        $areaName = '';
        $trailId = null;
        $trailName = '';

        // A blog post could be linked to any resource, so we'll make sure we have the right one first, and apply the prompts manually.
        if (get_class($resource) === 'App\Models\Blog\Post') {
            $post = $resource;
            if ($post->resource_table === 'trails') {
                if (!isset($post->trail) || !isset($post->area)) {
                    $post->trail = Trail::with('area')->where('id', $post->resource_id)->first();
                    if (!$post->trail) {
                        return null;
                    }
                }

                $areaName = $post->trail->area->name;
                $trailId = $post->trail->id;
                $trailName = $post->trail->name;

                if ($post->openai_prompts) {
                    $prompts = $post->openai_prompts;
                } else {
                    $prompts = OpenAIPrompt::where(['source_table' => 'blog_posts', 'source_id' => $post->id])->get();
                }
            }
        }

        if (get_class($resource) === 'App\Models\Trail') {
            $trail = $resource;
            if (!isset($trail->area)) {
                $trail->area = Area::where('id', $trail->area_id)->first();
            }
            $areaName = $trail->area->name;
            $trailId = $trail->id;
            $trailName = $trail->name;
            $prompts = $resource->openai_prompts;
        }

        if (isset($prompts))  {
            foreach ($prompts as $prompt) {
                $systemPrompt = "You are assisting a hiker looking at a specific trail page. Each trail has an ID and your task is to provide context-sensitive responses about the trail and answer their questions.";
                $userPrompt = "I'm on the trail page for " . $trailName . ", which has ID 'trail_" . $trailId . "' and is in the " . $areaName . " area. User question: '" . $prompt->prompt . "'";
                $data .= $this->createOpenAIFinetuneJson($systemPrompt, $userPrompt, $prompt->answer);
            }
        }

        return $data;
    }

    public function generatePromptsWithOpenAI(string $text, $maxTokens = 2000) {
        $result = OpenAI::chat()->create([
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role" => "system", "content" => "You are a helpful assistant that creates a FAQ of things hikers would like to know about a trail. Separate out content into many questions and output as JSON. Reword the answers while keeping useful information that hikers would find interesting. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else."],
                ["role" => "user", "content" => $text]
            ],
            "max_tokens" => $maxTokens,
        ]);
        $outputContent = $result['choices'][0]['message']['content'];
        $outputContent = str_replace('```jsonl', '', $outputContent);
        $outputContent = str_replace('```', '', $outputContent);
        $outputContent = str_replace('json', '', $outputContent);
        return trim($outputContent);
    }

    public function cleanTextWithWGPT(string $text): ?string {
        $cmd = 'wgpt "Clean up, fix misspellings and grammar, and format the following text for a travel web site about Kanab and southern Utah, with information about hiking trails in the area: ';
        $cmd .= str_replace('"', '', $text) . '"';
        return shell_exec($cmd);
    }

    public function identifyActivityTypeWithOpenAI(string $text): ?string {
        $activityTypes = implode(', ', ActivityType::get()->pluck('name')->toArray());
        $result = OpenAI::chat()->create([
            'model' => Config::get('openai.model'),
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
            'model' => Config::get('openai.model'),
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
            'model' => Config::get('openai.model'),
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
            'model' => Config::get('openai.model'),
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
