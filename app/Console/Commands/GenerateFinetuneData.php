<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Blog\Post;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GenerateFinetuneData extends Command
{
    const WORD_LIMIT_PER_FILE = 40000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:finetune {--areas} {--locations} {--trails} {--show} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate fine-tune data for OpenAI.';

    private $openAIService;
    private $wordCount = [];

    protected $showOutput;

    protected $outputFile;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doAreas = $this->option('areas');
        $doLocations = $this->option('locations');
        $doTrails = $this->option('trails');
        $this->showOutput = $this->option('show');
        $this->outputFile = $this->option('output');
        $this->openAIService = new OpenAIService();

        $areas = Area::all();
        foreach ($areas as $area) {
            $this->wordCount[$area->id] = 0;
        }

        if ($this->output && file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }

        if ($doAreas) {
            $this->processAreas();
        }

        if ($doLocations) {
            $this->processLocations();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    public function processAreas()
    {
        $posts = Post::with('openai_prompts')
            ->whereHas('openai_prompts')
            ->where('resource_table', 'areas')
            ->whereNotNull('resource_id')
            ->get();
        foreach ($posts as $post) {
            $area = Area::where('id', $post->resource_id)->first();
            if (!$area) {
                continue;
            }
            $json = $this->openAIService->generateFinetuneData($post);
            $this->wordCount[$area->id] += str_word_count($json);
            $seqOutputFile = $this->outputFile . '-area-' . $area->id . '-' . ceil($this->wordCount[$area->id] / self::WORD_LIMIT_PER_FILE) . '.jsonl';
            file_put_contents($seqOutputFile, $json, FILE_APPEND);
            if ($this->showOutput) {
                echo $json;
            }
        }
    }

    /**
     * TODO: Fix this.
     */
    public function processLocations()
    {
        $posts = Post::with('openai_prompts')
            ->whereHas('openai_prompts')
            ->where('resource_table', 'locations')
            ->whereNotNull('resource_id')
            ->get();
        foreach ($posts as $post) {
            $location = Location::with('area')->where('id', $post->resource_id)->first();
            if (!$location) {
                continue;
            }
            $post->location = $location;
            $post->area = $location->area;
            $json = $this->openAIService->generateFinetuneData($post);
            $this->wordCount[$post->area->id] += str_word_count($json);
            $seqOutputFile = $this->outputFile . '-area-' . $post->area->id . '-' . ceil($this->wordCount[$post->area->id] / self::WORD_LIMIT_PER_FILE) . '.jsonl';
            if ($this->outputFile) {
                file_put_contents($seqOutputFile, $json, FILE_APPEND);
            }
            if ($this->showOutput) {
                echo $json;
            }
        }

        $locations = Location::with('openai_prompts')
            ->with('area')
            ->whereHas('openai_prompts')
            ->get();
        foreach ($locations as $location) {
            $json = $this->openAIService->generateFinetuneData($location);
            $this->wordCount[$location->area->id] += str_word_count($json);
            $seqOutputFile = $this->outputFile . '-area-' . $location->area->id . '-' . ceil($this->wordCount[$location->area->id] / self::WORD_LIMIT_PER_FILE) . '.jsonl';
            if ($this->outputFile) {
                file_put_contents($seqOutputFile, $json, FILE_APPEND);
            }
            if ($this->showOutput) {
                echo $json;
            }
        }
    }

    public function processTrails()
    {
        $posts = Post::with('openai_prompts')
            ->whereHas('openai_prompts')
            ->where('resource_table', 'trails')
            ->whereNotNull('resource_id')
            ->get();
        foreach ($posts as $post) {
            $trail = Trail::with('area')->where('id', $post->resource_id)->first();
            if (!$trail) {
                continue;
            }
            $post->trail = $trail;
            $post->area = $trail->area;
            $json = $this->openAIService->generateFinetuneData($post);
            $this->wordCount[$post->area->id] += str_word_count($json);
            $seqOutputFile = $this->outputFile . '-area-' . $post->area->id . '-' . ceil($this->wordCount[$post->area->id] / self::WORD_LIMIT_PER_FILE) . '.jsonl';
            if ($this->outputFile) {
                file_put_contents($seqOutputFile, $json, FILE_APPEND);
            }
            if ($this->showOutput) {
                echo $json;
            }
        }

        $trails = Trail::with('openai_prompts')
            ->with('area')
            ->whereHas('openai_prompts')
            ->get();
        foreach ($trails as $trail) {
            $json = $this->openAIService->generateFinetuneData($trail);
            $this->wordCount[$trail->area->id] += str_word_count($json);
            $seqOutputFile = $this->outputFile . '-area-' . $post->area->id . '-' . ceil($this->wordCount[$trail->area->id] / self::WORD_LIMIT_PER_FILE) . '.jsonl';
            if ($this->outputFile) {
                file_put_contents($seqOutputFile, $json, FILE_APPEND);
            }
            if ($this->showOutput) {
                echo $json;
            }
        }
    }
}
