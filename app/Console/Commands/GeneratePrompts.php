<?php

namespace App\Console\Commands;

use App\Models\Blog\Post;
use App\Models\Guide;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Trail;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GeneratePrompts extends Command
{
    const MAX_PROMPTS = 30;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:generateprompts {--guides} {--locations} {--posts} {--trails}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate prompts with OpenAI.';

    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doGuides = $this->option('guides');
        $doLocations = $this->option('locations');
        $doPosts = $this->option('posts');
        $doTrails = $this->option('trails');
        $this->openAIService = new OpenAIService();

        if ($doGuides) {
            $this->processGuides();
        }

        if ($doLocations) {
            $this->processLocations();
        }

        if ($doPosts) {
            $this->processPosts();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    public function processGuides($maxPrompts = self::MAX_PROMPTS)
    {
        // Process blog posts linked to guides.
        $this->processPosts('guides');

        // Process guides text that's been extracted or generated.
        $guides = Guide::with(['openai_prompts', 'text'])->inRandomOrder()->get();
        foreach ($guides as $obj) {
            if (count($obj->openai_prompts) <= $maxPrompts) {
                foreach ($obj->text as $text) {
                    $promptsJson = $this->openAIService->generateGuidePrompts(strip_tags($text->clean));
                    $prompts = json_decode($promptsJson);
                    foreach ($prompts as $prompt) {
                        OpenAIPrompt::create([
                            'source_table' => 'guides',
                            'source_id' => $obj->id,
                            'prompt' => $prompt->prompt,
                            'answer' => $prompt->answer,
                        ]);
                    }
                }
            }
        }
    }

    public function processLocations($maxPrompts = self::MAX_PROMPTS)
    {
        $this->processPosts('locations');
        $locations = Location::with(['openai_prompts', 'text'])->inRandomOrder()->get();
        foreach ($locations as $obj) {
            if (count($obj->openai_prompts) <= $maxPrompts) {
                foreach ($obj->text as $text) {
                    $promptsJson = $this->openAIService->generateLocationPrompts(strip_tags($text->clean));
                    $prompts = json_decode($promptsJson);
                    foreach ($prompts as $prompt) {
                        OpenAIPrompt::create([
                            'source_table' => 'locations',
                            'source_id' => $obj->id,
                            'prompt' => $prompt->prompt,
                            'answer' => $prompt->answer,
                        ]);
                    }
                }
            }
        }
    }

    public function processPosts($resourceTable = null, $maxPrompts = self::MAX_PROMPTS)
    {
        $postsQuery = Post::with('openai_prompts')
            ->leftJoin('openai_prompts', function ($join) {
                $join->on('blog_posts.id', '=', 'openai_prompts.source_id')
                    ->where('openai_prompts.source_table', '=', 'blog_posts');
            })
            ->select('blog_posts.*')  // Select only columns from blog_posts
            ->groupBy('blog_posts.id')
            ->havingRaw('COUNT(openai_prompts.id) < ' . $maxPrompts)
            ->inRandomOrder();
        if ($resourceTable !== null) {
            $postsQuery = $postsQuery->where('resource_table', $resourceTable);
        } else {
            $postsQuery = $postsQuery->whereNotNull('resource_table');
        }
        $posts = $postsQuery->get();
        foreach ($posts as $obj) {
            $promptsCount = count($obj->openai_prompts);
            while ($promptsCount <= $maxPrompts) {
                switch ($obj->resource_table) {
                    case 'guides':
                        $guide = Guide::where(['id' => $obj->resource_id, 'is_usable' => 1])->first();
                        if ($guide) {
                            $promptsJson = $this->openAIService->generateGuidePrompts(strip_tags($obj->content));
                        } else {
                            continue 2;
                        }
                        break;
                    default:
                        $promptsJson = $this->openAIService->generateTrailPrompts(strip_tags($obj->content));
                }
                $prompts = json_decode($promptsJson);
                foreach ($prompts as $prompt) {
                    OpenAIPrompt::create([
                        'source_table' => 'blog_posts',
                        'source_id' => $obj->id,
                        'prompt' => $prompt->prompt,
                        'answer' => $prompt->answer,
                    ]);
                    $promptsCount++;
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

    public function processTrails($maxPrompts = self::MAX_PROMPTS)
    {
        $trails = Trail::with(['openai_prompts', 'text'])->whereHas('text')->inRandomOrder()->get();
        foreach ($trails as $obj) {
            if (count($obj->openai_prompts) <= $maxPrompts) {
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

                $promptsJson = $this->openAIService->generateTrailPrompts($prompt);
                $prompts = json_decode($promptsJson);
                foreach ($prompts as $prompt) {
                    OpenAIPrompt::create([
                        'source_table' => 'trails',
                        'source_id' => $obj->id,
                        'prompt' => $prompt->prompt,
                        'answer' => $prompt->answer,
                    ]);
                }

                foreach ($obj->text as $text) {
                    $promptsJson = $this->openAIService->generateTrailPrompts($text->clean);
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
}
