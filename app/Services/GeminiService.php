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
 */
namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Assistant\Assistant;
use App\Models\Event;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Restaurant;
use App\Models\ResourceText;
use App\Models\Trail;
use App\ResourceEnum;
use Exception;
use Gemini\Client;
use Gemini\Resources\ChatSession;
use Gemini\Resources\GenerativeModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class GeminiService
{
    public const DEFAULT_TEMPERATURE = 0.2;
    public const DEFAULT_TOP_P = 1.0;
    public const GENERAL_ASSISTANT_NAME = 'General';
    public const GENERAL_ASSISTANT_INSTRUCTIONS = 'You are assisting a traveler to southern Utah. Provide helpful answers to their questions.';
    public const GENERATE_GUIDE_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ about $$TOPIC$$. Separate out content into many questions and output as JSON. Reword the answers while keeping useful, factual information. Some opinions are okay if they don\'t advertise a specific product. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';
    public const GENERATE_LOCATION_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ of things hikers and tourists would like to know about a trail or location. Separate out content into many questions and output as JSON. Reword the answers while keeping useful information that hikers and tourists would find interesting. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';
    public const GENERATE_TRAIL_PROMPTS_SYSTEM = 'You are a helpful assistant that creates a FAQ of things hikers and tourists would like to know about a trail or location. Separate out content into many questions and output as JSON. Reword the answers while keeping useful information that hikers and tourists would find interesting. Each entry in the JSON array only has two attributes: prompt and answer. ONLY output JSON, nothing else.';
    public const MAX_SECONDS_FOR_QUERY = 15;
    public const POLL_SECONDS = 1;

    protected Client $client;
    protected ?GenerativeModel $defaultModelInstance = null;
    protected string $defaultModelName;

    public function __construct(string $apiKey = null, string $defaultModel = null)
    {
        $apiKey = $apiKey ?: config('gemini.api_key');
        if (!$apiKey) {
            throw new \InvalidArgumentException('Gemini API key not configured.');
        }
        $this->client = new Client($apiKey);
        $this->defaultModelName = $defaultModel ?: Config('gemini.model');
    }

    protected function getDefaultModel(): GenerativeModel
    {
        if (!$this->defaultModelInstance) {
            $this->defaultModelInstance = $this->client->generativeModel($this->defaultModelName);
        }
        return $this->defaultModelInstance;
    }

    public function generateText(string $prompt, array $generationConfig = [], string $model = null): ?string
    {
        $modelToUse = $model ?: $this->defaultModelName;
        try {
            $result = $this->client->generativeModel($modelToUse)->generateContent($prompt, $generationConfig);
            return $result?->text();
        } catch (\Exception $e) {
            Log::error("Gemini API Error (generateText with model {$modelToUse}): " . $e->getMessage());
            return null;
        }
    }

    public function startChat(array $startChatConfig = [], string $model = null): ChatSession
    {
        $modelToUse = $model ?: $this->defaultModelName;
        return $this->client->generativeModel($modelToUse)->startChat($startChatConfig);
    }

    public function sendMessage(ChatSession $chatSession, string $message, array $generationConfig = [], string $model = null): ?string
    {
        $modelToUse = $model ?: $this->defaultModelName;
        try {
            $response = $chatSession->sendMessage($message, $generationConfig);
            return $response?->text();
        } catch (\Exception $e) {
            Log::error("Gemini API Error (sendMessage with model {$modelToUse}): " . $e->getMessage());
            return null;
        }
    }

    public function query($prompt, $maxTokens = 500, $model = null, Assistant $assistant, $threadId = null) {
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
            'max_prompt_tokens' => 5000,
            'max_completion_tokens' => 5000,
            'temperature' => self::DEFAULT_TEMPERATURE,
            'top_p' => self::DEFAULT_TOP_P,
            'truncation_strategy' => [
                'type' => 'last_messages',
                'last_messages' => 1,
            ],
        ]);

        // Poll until the run is complete.
        $startTime = time();

        do {
            // TODO: Error-handle when the time limit self::MAX_SECONDS_FOR_QUERY is reached.
            sleep(self::POLL_SECONDS);
            $run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
            Log::info($run->toArray());
        } while ($run->status !== 'completed' && time() < $startTime + self::MAX_SECONDS_FOR_QUERY * 1000);

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
//            'total_tokens' => $result['usage']['totalTokens'],
//            'processing_ms' => $result['meta']['openai']['processingMs'],
        ];
    }

    public function generateGuidePrompts(string $text) {
        return $this->generatePromptsWithGemini($text, self::GENERATE_GUIDE_PROMPTS_SYSTEM);
    }

    public function generateLocationPrompts(string $text) {
        return $this->generatePromptsWithGemini($text, self::GENERATE_LOCATION_PROMPTS_SYSTEM);
    }

    public function generateTrailPrompts(string $text) {
        return $this->generatePromptsWithGemini($text, self::GENERATE_TRAIL_PROMPTS_SYSTEM);
    }

    private function generatePromptsWithGemini(string $text, string $systemMessage, $maxTokens = 2000) {
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
}
