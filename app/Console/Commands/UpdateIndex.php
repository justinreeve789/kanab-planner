<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Searchable;

class UpdateIndex extends Command
{
    const CHUNK_SIZE = 250;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:index {--events} {--locations} {--posts} {--prompts} {--restaurants} {--text} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the search engine';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');

        $events = $this->option('events');
        $locations = $this->option('locations');
        $blog_posts = $this->option('posts');
        $openai_prompts = $this->option('prompts');
        $resource_text = $this->option('text');
        $restaurants = $this->option('restaurants');
        $trails = $this->option('trails');

        $tables = [
            'blog_posts' => 'App\\Models\\Blog\\Post',
            'events' => 'App\\Models\\Event',
            'locations' => 'App\\Models\\Location',
            'openai_prompts' => 'App\\Models\\OpenAIPrompt',
            'resource_text' => 'App\\Models\\ResourceText',
            'restaurants' => 'App\\Models\\Restaurant',
            'trails' => 'App\\Models\\Trail',
        ];

        // Artisan::call('scout:flush "\\\App\\\Models\\\Location"');

        foreach ($tables as $table => $modelClass) {
            if ($$table === true) {
                // Artisan::call('scout:import "' . $model . '"');
                $model = new $modelClass;
                $model::chunk(self::CHUNK_SIZE, function ($items) {
                    foreach ($items as $item) {
                        $item->searchable();
                    }
                });
            }
        }

//        $doEvents = $this->option('events');
//        $doLocations = $this->option('locations');
//        $doPrompts = $this->option('prompts');
//        $doRestaurants = $this->option('restaurants');
//        $doTrails = $this->option('trails');
//
//        if ($doEvents) {
//            $this->processEvents();
//        }
//
//        if ($doLocations) {
//            $this->processLocations();
//        }
//
//        if ($doPrompts) {
//            $this->processPrompts();
//        }
//
//        if ($doRestaurants) {
//            $this->processRestaurants();
//        }
//
//        if ($doTrails) {
//            $this->processTrails();
//        }
    }
}
