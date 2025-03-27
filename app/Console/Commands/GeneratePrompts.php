<?php

namespace App\Console\Commands;

use App\Models\Blog\Post;
use App\Models\OpenAIPrompt;
use App\Models\Trail;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GeneratePrompts extends Command
{
    const MAX_PROMPTS = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:generateprompts {--posts} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate prompts for blog posts with OpenAI.';

    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doPosts = $this->option('posts');
        $doTrails = $this->option('trails');
        $this->openAIService = new OpenAIService();

        if ($doPosts) {
            $this->processPosts();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    public function processPosts()
    {
        $posts = Post::with('openai_prompts')->get();
        foreach ($posts as $obj) {
            if (count($obj->openai_prompts) <= self::MAX_PROMPTS) {
                $promptsJson = $this->openAIService->generatePromptsWithOpenAI(strip_tags($obj->content));
                $prompts = json_decode($promptsJson);
                foreach ($prompts as $prompt) {
                    OpenAIPrompt::create([
                        'source_table' => 'blog_posts',
                        'source_id' => $obj->id,
                        'prompt' => $prompt->prompt,
                        'answer' => $prompt->answer,
                    ]);
                }
                OpenAIPrompt::whereLike('answer', '%Gaia GPS%')
                    ->where('source_table', 'blog_posts')
                    ->where('source_id', $obj->id)
                    ->delete();
                OpenAIPrompt::whereLike('answer', '%trail map%')
                    ->where('source_table', 'blog_posts')
                    ->where('source_id', $obj->id)
                    ->delete();
            }
        }
    }

    public function processTrails()
    {
        $trails = Trail::with(['openai_prompts', 'text'])->get();
        foreach ($trails as $obj) {
            if (count($obj->openai_prompts) <= self::MAX_PROMPTS) {
                $prompt = '';
                if (!empty($obj->text[0])) {
                    $prompt .= $obj->text[0]->clean;
                }
                $prompt .= $obj->description . ' ' . $obj->description_alt . '\n\n';
                if ($obj->difficulty) {
                    $prompt .= 'Difficulty: ' . $obj->difficulty . '\n';
                }
                if ($obj->elevation_high_ft) {
                    $prompt .= 'Highest elevation (in feet): ' . $obj->elevation_low_ft . '\n';
                }
                if ($obj->elevation_low_ft) {
                    $prompt .= 'Lowest elevation (in feet): ' . $obj->elevation_low_ft . '\n';
                }
                if ($obj->distance_miles) {
                    $prompt .= 'Trail length (in miles): ' . $obj->distance_miles . '\n';
                }
                if ($obj->distance_type) {
                    $prompt .= 'Trail type: ' . $obj->distance_type . '\n';
                }
                if ($obj->incline_ft) {
                    $prompt .= 'Incline (in feet): ' . $obj->incline_ft . '\n';
                }
                if ($obj->decline_ft) {
                    $prompt .= 'Decline (in feet): ' . $obj->decline_ft . '\n';
                }
                if ($obj->average_grade_pt) {
                    $prompt .= 'Average grade (in percentage): ' . $obj->average_grade_pt . '\n';
                }
                if ($obj->max_grade_pt) {
                    $prompt .= 'Maximum grade (in percentage): ' . $obj->max_grade_pt . '\n';
                }
                if ($obj->features) {
                    $prompt .= 'Features: ' . $obj->features . '\n';
                }
                if ($obj->accessibility) {
                    $prompt .= 'Accessibility: ' . $obj->accessibility . '\n';
                }
                if ($obj->pets_allowed) {
                    $prompt .= 'Dogs allowed: ' . $obj->pets_allowed . '\n';
                }
                if ($obj->latitude) {
                    $prompt .= 'Latitude: ' . $obj->latitude . '\n';
                }
                if ($obj->longitude) {
                    $prompt .= 'Longitude: ' . $obj->longitude . '\n';
                }

                $promptsJson = $this->openAIService->generatePromptsWithOpenAI($prompt);
                $prompts = json_decode($promptsJson);
                foreach ($prompts as $prompt) {
                    OpenAIPrompt::create([
                        'source_table' => 'trails',
                        'source_id' => $obj->id,
                        'prompt' => $prompt->prompt,
                        'answer' => $prompt->answer,
                    ]);
                }
            }
        }
    }
}
