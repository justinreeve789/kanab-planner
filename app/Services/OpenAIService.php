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
 * - If an answer references another trail, make it a hyperlink. Tell it to add <trail_#>...</trail_#> around the trail name.
 * - If asked about closures, don't mention a date (e.g. "As of October 2023"). Point to a specific URL with more information
 * if none is available.
 * - Avoid recommending anything in the South Rim.
 * - If an event, location, restaurant, or trail is recommended, give the user an option to add it to the itinerary.
 * - If a change in topic is detected, automatically switch to that topic in the chat window.
 * - If there's any mention of a non-summer season at the Grand Canyon, include a disclaimer that the North Rim isn't open at March.
 * - When a suggestion for an activity is made, ask the user if they want to add it to the itinerary.
 * - If asked about nearby trails or restaurants, use a Haversine search to find nearby resources rather than OpenAI. If
 * they're trails, find trails of similar or shorter length. Then construct short snippets about the trails.
 * - If asked about how to get to a place, open Google Maps with directions.
 * - If asked about family-friendly activities in the area or stuff to do, reference the events table.
 */
namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Assistant\Assistant;
use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Restaurant;
use App\Models\ResourceText;
use App\Models\Trail;
use App\ResourceEnum;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public const DEFAULT_TEMPERATURE = 0.2;
    public const DEFAULT_TOP_P = 1.0;
    public const GENERAL_ASSISTANT_NAME = 'General';
    public const GENERAL_ASSISTANT_INSTRUCTIONS = 'You are assisting a traveler to southern Utah. Provide helpful answers to their questions, giving specific examples and recommendations.';
    public const GENERATE_GUIDE_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ about $$TOPIC$$. Separate out content into many questions and output as JSON. Reword the answers while keeping useful, factual information. Some opinions are okay if they don\'t advertise a specific product. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';
    public const GENERATE_LOCATION_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ of things hikers and tourists would like to know about a trail or location. Separate out content into many questions and output as JSON. Reword the answers while keeping useful information that hikers and tourists would find interesting. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';
    public const GENERATE_TRAIL_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ of things hikers and tourists would like to know about a trail or location. Separate out content into many questions and output as JSON. Reword the answers while keeping useful information that hikers and tourists would find interesting. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';

    public const MAX_PROMPT_TOKENS = 500;
    public const MAX_SECONDS_FOR_QUERY = 15;
    public const POLL_SECONDS = 1;

    /**
     * Use the Assistants API to query the virtual assistant.
     * TODO: Use the Responses API when threads are better-supported.
     *
     * @param $prompt
     * @param $maxTokens
     * @param $model
     * @param $threadId
     * @return object
     */
    public function query($prompt, $maxTokens = 750, $model = null, Assistant $assistant, $threadId = null) {
        if ($model === null) {
            $model = Config::get('openai.model');
        }

        // Create a thread if it doesn't exist.
        if ($threadId === null) {
            $thread = OpenAI::threads()->create([]);
            $threadId = $thread->id;
        }

        // Add a message to the thread.
        OpenAI::threads()->messages()->create($threadId, [
            'role' => 'user',
            'content' => $prompt,
        ]);

        $run = OpenAI::threads()->runs()->create($threadId, [
            'assistant_id' => $assistant->assistant_id,
            'max_prompt_tokens' => self::MAX_PROMPT_TOKENS,
            'max_completion_tokens' => $maxTokens,
            'temperature' => self::DEFAULT_TEMPERATURE,
            'top_p' => self::DEFAULT_TOP_P,
        ]);

        // Poll until the run is complete.
        $startTime = time();

        do {
            // TODO: Error-handle when the time limit self::MAX_SECONDS_FOR_QUERY is reached.
            sleep(self::POLL_SECONDS);
            $run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
            Log::info($run->toArray());
        } while ($run->status !== 'completed' && $run->status !== 'failed' && time() < $startTime + self::MAX_SECONDS_FOR_QUERY);

        if ($run->status === 'failed') {
            Log::error('Could not run query:', $run->toArray());
            return null;
        }

        // Retrieve messages.
        $result = OpenAI::threads()->messages()->list($threadId);

        return (object) [
            'id' => $result->data[0]->id,
            'thread_id' => $threadId,
            'model' => $model,
            'content' => $result->data[0]->content[0]->text->value,
//            'role' => $result['choices'][0]['message']['role'],
//            'function_call' => $result['choices'][0]['message']['functionCall'],
//            'tool_calls' => $result['choices'][0]['message']['toolCalls'],
//            'prompt_tokens' => $result['usage']['promptTokens'],
//            'completion_tokens' => $result['usage']['completionTokens'],
//            'total_tokens' => $result->usage->totalTokens,
//            'processing_ms' => $result['meta']['openai']['processingMs'],
        ];
    }

    /**
     * TODO: Use OpenAI threads to track topics. Thread ids should follow this format: {userId/sessionId_resourceType_resourceId}
     * TODO: Use the $activityType to provide specific instructions, particularly about trails.
     */
    public function createAssistant($model = null, $resource = null, $activityType = null) {
        if ($model === null) {
            $model = Config::get('openai.model');
        }

        // Different resources will use different instructions.
        if ($resource) {
            switch (get_class($resource)) {
                case Restaurant::class:
                    $name = 'Restaurant ' . $resource->id;
                    $instructions = 'You are assisting a traveler looking at a restaurant page about ' . $resource->name . '  (ID "' . $resource->type . '_' . $resource->id . '") in the ' . $resource->location->area->name . ' area. Your task is to provide context-sensitive responses about the restaurant and answer their questions.';
                    break;
                case Trail::class:
                    $name = 'Trail ' . $resource->id;
                    $instructions = 'You are assisting a hiker looking at a trail page about the ' . $resource->name . ' Trail (ID "trail_' . $resource->id . '") in the ' . $resource->area->name . ' area. Your task is to provide context-sensitive responses about the trail and answer their questions.';
                    break;
                default:
                    $name = 'Resource ' . $resource->type . ' ' . $resource->id;
                    $instructions = 'You are assisting a traveler looking at a page about ' . $resource->name . ' (ID "' . $resource->type . '_' . $resource->id . '") in the ' . $resource->area->name . ' area. Your task is to provide context-sensitive answers with specific details and recommendations to their questions.';
                    break;
            }
        } else {
            $instructions = self::GENERAL_ASSISTANT_INSTRUCTIONS;
            $name = self::GENERAL_ASSISTANT_NAME;
        }

        $assistant = OpenAI::assistants()->create([
            'model' => $model,
            'name' => $name,
            'instructions' => $instructions,
        ]);

        return $assistant;
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

    private function generateFinetuneDataForArea($area) {
        $data = '';
        if (get_class($area) === Area::class) {
            $areaName = $area->name;
            $areaId = $area->id;
            $prompts = $area->openai_prompts;
            if (isset($prompts)) {
                foreach ($prompts as $prompt) {
                    $systemPrompt = "You are assisting a traveler looking at an area page about the " . $areaName . " area. The page has an ID and your task is to provide context-sensitive responses about the area and answer their questions.";
                    $userPrompt = "I'm on the page for the " . $areaName . ", which has ID 'area_" . $areaId . "'. User question: '" . $prompt->prompt . "'";
                    $data .= $this->createOpenAIFinetuneJson($systemPrompt, $userPrompt, $prompt->answer);
                }
            }
        }
        return $data;
    }

    private function generateFinetuneDataForLocation($resource) {
        $data = '';
        if (get_class($resource) === Location::class) {
            if (!isset($resource->area)) {
                $resource->area = Area::where('id', $resource->area_id)->first();
            }
            $areaName = $resource->area->name;
            $resourceType = $resource->type;
            $resourceId = $resource->id;
            $resourceName = $resource->name;
            $prompts = $resource->openai_prompts;
            if (isset($prompts)) {
                foreach ($prompts as $prompt) {
                    $systemPrompt = "You are assisting a traveler looking at a specific location page. Each location is a point of interest that has an ID and your task is to provide context-sensitive responses about the location and answer their questions.";
                    $userPrompt = "I'm on the location page for " . $resourceName . ", which has ID '" . $resourceType . "_" . $resourceId . "' and is in the " . $areaName . " area. User question: '" . $prompt->prompt . "'";
                    $data .= $this->createOpenAIFinetuneJson($systemPrompt, $userPrompt, $prompt->answer);
                }
            }
        }
        return $data;
    }

    private function generateFinetuneDataForRestaurant($resource) {
        $data = '';
        if (get_class($resource) === Restaurant::class) {
            if (!isset($resource->area)) {
                $resource->area = Area::where('id', $resource->area_id)->first();
            }
            $areaName = $resource->area->name;
            $resourceType = $resource->type;
            $resourceId = $resource->id;
            $resourceName = $resource->name;
            $prompts = $resource->openai_prompts;
            if (isset($prompts)) {
                foreach ($prompts as $prompt) {
                    $systemPrompt = "You are assisting a user looking at a specific restaurant page. Each restaurant has an ID and your task is to provide context-sensitive responses about the restaurant and answer their questions.";
                    $userPrompt = "I'm on the restaurant page for " . $resourceName . ", which has ID '" . $resourceType . "_" . $resourceId . "' and is in the " . $areaName . " area. User question: '" . $prompt->prompt . "'";
                    $data .= $this->createOpenAIFinetuneJson($systemPrompt, $userPrompt, $prompt->answer);
                }
            }
        }
        return $data;
    }

    private function generateFinetuneDataForTrail($resource) {
        $data = '';
        if (get_class($resource) === Trail::class) {
            if (!isset($resource->area)) {
                $resource->area = Area::where('id', $resource->area_id)->first();
            }
            $areaName = $resource->area->name;
            $resourceType = $resource->type;
            $resourceId = $resource->id;
            $resourceName = $resource->name;
            $prompts = $resource->openai_prompts;
            if (isset($prompts)) {
                foreach ($prompts as $prompt) {
                    $systemPrompt = "You are assisting a hiker looking at a specific trail page. Each trail has an ID and your task is to provide context-sensitive responses about the trail and answer their questions.";
                    $userPrompt = "I'm on the trail page for " . $resourceName . ", which has ID '" . $resourceType . "_" . $resourceId . "' and is in the " . $areaName . " area. User question: '" . $prompt->prompt . "'";
                    $data .= $this->createOpenAIFinetuneJson($systemPrompt, $userPrompt, $prompt->answer);
                }
            }
        }
        return $data;
    }

    public function generateFinetuneData($resource) {
        if (!isset($resource->openai_prompts)) {
            throw new Exception('Missing OpenAI prompts.');
        }

        // A blog post could be linked to any resource, so we'll make sure we have the right one first, and apply the prompts manually.
        if (get_class($resource) === Post::class) {
            $post = $resource;
            if ($post->openai_prompts) {
                $prompts = $post->openai_prompts;
            } else {
                $prompts = OpenAIPrompt::where(['source_table' => 'blog_posts', 'source_id' => $post->id])->get();
            }

            if ($post->resource_table === ResourceEnum::AREAS_TABLE) {
                $area = Area::where('id', $post->resource_id)->first();
                if ($area) {
                    $area->openai_prompts = $prompts;
                    return $this->generateFinetuneDataForArea($area);
                }
            }

            if ($post->resource_table === ResourceEnum::LOCATIONS_TABLE) {
                $location = Location::where('id', $post->resource_id)->first();
                if ($location) {
                    $location->openai_prompts = $prompts;
                    return $this->generateFinetuneDataForLocation($location);
                }
            }

            if ($post->resource_table === ResourceEnum::RESTAURANTS_TABLE) {
                $restaurant = Restaurant::where('id', $post->resource_id)->first();
                if ($restaurant) {
                    $restaurant->openai_prompts = $prompts;
                    return $this->generateFinetuneDataForRestaurant($restaurant);
                }
            }

            if ($post->resource_table === ResourceEnum::TRAILS_TABLE) {
                $trail = Trail::where('id', $post->resource_id)->first();
                if ($trail) {
                    $trail->openai_prompts = $prompts;
                    return $this->generateFinetuneDataForTrail($trail);
                }
            }
        }

        if (get_class($resource) === Location::class) {
            if (!isset($resource->openai_prompts)) {
                $resource->openai_prompts = OpenAIPrompt::where(['source_table' => $resource->getTable(), 'source_id' => $resource->id])->get();
            }
            return $this->generateFinetuneDataForLocation($resource);
        }

        if (get_class($resource) === Trail::class) {
            if (!isset($resource->openai_prompts)) {
                $resource->openai_prompts = OpenAIPrompt::where(['source_table' => $resource->getTable(), 'source_id' => $resource->id])->get();
            }
            return $this->generateFinetuneDataForTrail($resource);
        }
    }

    public function summarizeReviewsOfTrails(string $text, $maxTokens = 2000) {
        $result = OpenAI::chat()->create([
            "model" => Config::get('openai.model'),
            "messages" => [
//                ["role" => "system", "content" => "You are a helpful assistant that analyzes reviews of hiking trails. Without sounding like ChatGPT or AI, only summarize the facts, recommendations, best time to visit, landmarks, things to do, wildlife but omit completely if nothing's mentioned, and overall experience. Don't indicate these came from reviews or mention 'guests' or 'visitors'."],
                ["role" => "system", "content" => "You are a helpful assistant that analyzes reviews of hiking trails. Without sounding like ChatGPT or AI, only summarize the facts, recommendations, best time to visit, landmarks, things to do, wildlife but omit completely if nothing's mentioned, and overall experience. Don't indicate these came from reviews or mention 'guests' or 'visitors'. Only output JSON and nothing else. Format as a JSON string containing only these fields: location-and-access, recommendations, best-time-to-visit, landmarks-and-things-to-do, wildlife, overall-experience."],
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

    public function generateGuidePrompts(string $text) {
        return $this->generatePromptsWithOpenAI($text, self::GENERATE_GUIDE_PROMPTS_SYSTEM);
    }

    public function generateLocationPrompts(string $text) {
        return $this->generatePromptsWithOpenAI($text, self::GENERATE_LOCATION_PROMPTS_SYSTEM);
    }

    public function generateTrailPrompts(string $text) {
        return $this->generatePromptsWithOpenAI($text, self::GENERATE_TRAIL_PROMPTS_SYSTEM);
    }

    private function generatePromptsWithOpenAI(string $text, string $systemMessage, $maxTokens = 2000) {
        $result = OpenAI::chat()->create([
            "model" => Config::get('openai.model'),
            "messages" => [
                ["role" => "system", "content" => $systemMessage],
                ["role" => "user", "content" => $text],
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

    public function promptsToAdd() {
        return [
            [
                'prompts' => [
                    'Give me trail maps.',
                    'I need trail maps.',
                    'Show me trail maps.',
                    'Trail maps',
                    'Do you have trail maps?',
                    'Just give me a map.',
                ],
                'answer' => 'You may browse the trails and find maps by selecting "Trails" from the menu. If you mention a specific trail, I can provide the map directly.',
            ],
            [
                'prompts' => [
                    'What restaurants are there?',
                    'Where can I eat?',
                    'I\'m hungry.',
                    'Restaurants nearby',
                ],
                'answer' => 'You may browse the restaurants by selecting "Restaurants" from the menu.',
            ],
            'prompts' => [
                'What\'s happening?',
                'Give me something to do.',
                'Stuff to do',
            ],
            'answer' => 'There are always events going on in the area! You may browse the local events by selecting "Events" from the menu.',
        ];
    }
}
