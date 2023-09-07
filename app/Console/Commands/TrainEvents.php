<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ResourceText;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class TrainEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'train:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train events for ChatGPT';

    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->openAIService = new OpenAIService();

        $events = Event::get();
        foreach ($events as $event) {
            $this->openAIService->trainEvent($event);
            die;
        }
    }
}
