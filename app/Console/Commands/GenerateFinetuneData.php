<?php

namespace App\Console\Commands;

use App\Models\Blog\Post;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GenerateFinetuneData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:finetune {--trails} {--show} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate fine-tune data for OpenAI.';

    private $openAIService;

    protected $showOutput;

    protected $outputFile;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doTrails = $this->option('trails');
        $this->showOutput = $this->option('show');
        $this->outputFile = $this->option('output');
        $this->openAIService = new OpenAIService();

        if ($this->output && file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }

        if ($doTrails) {
            $this->processTrails();
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
            if ($this->outputFile) {
                file_put_contents($this->outputFile, $json, FILE_APPEND);
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
            if ($this->outputFile) {
                file_put_contents($this->outputFile, $json, FILE_APPEND);
            }
            if ($this->showOutput) {
                echo $json;
            }
        }
    }
}
