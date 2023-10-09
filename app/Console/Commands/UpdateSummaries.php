<?php

namespace App\Console\Commands;

use App\Models\ResourceText;
use App\Models\Trail;
use App\Services\ConversionService;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class UpdateSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:summaries {--events} {--locations} {--restaurants} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use OpenAI to update summaries and descriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->openAIService = new OpenAIService();

        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->options('trails');

        if ($doEvents) {
            $this->processEvents();
        }

        if ($doRestaurants) {
            $this->processRestaurants();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    protected function processTrails()
    {
        $trails = Trail::whereDoesntHave('text', function($query) {
            $query->where('type', 'short');
        })->with('text')->get();
        foreach ($trails as $trail) {
            if ($trail->description || $trail->description_alt) {
                $query = $trail->name . ' ' . $trail->description . ' '. $trail->description_alt;
                if (isset($trail->text->clean)) {
                    $query .= ' ' . $trail->text->clean;
                } else if (isset($trail->text->raw)) {
                    $query .= ' ' . $trail->text->raw;
                }
                $text = $this->openAIService->createShortDescriptionForResourceWithWithOpenAI($query);
                if ($text) {
                    ResourceText::updateOrCreate(['resource_table' => 'trails', 'resource_id' => $trail->id, 'type' => 'short'], [
                        'clean' => $text,
                    ]);
                }
                $this->info($text);
            }
        }
    }
}
