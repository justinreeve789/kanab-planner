<?php

namespace App\Console\Commands;

use App\Models\ResourceText;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class CleanResourceText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:clean-resource-text';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up the resource text';

    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->openAIService = new OpenAIService();
        $resourceText = ResourceText::whereNull('clean')->orWhere('clean', '---')->get();
        foreach ($resourceText as $rt) {
            $text = $this->openAIService->cleanText($rt->raw);
            echo $text;
            $rt->clean = $text;
            $rt->save();
        }
    }
}
