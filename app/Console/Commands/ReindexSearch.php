<?php

namespace App\Console\Commands;

use App\Models\Accommodation;
use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\Guide;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Trail;
use Illuminate\Console\Command;

/**
 * Rebuild every Meilisearch index from the database: apply the index settings in config/scout.php, then import
 * each searchable model.
 */
class ReindexSearch extends Command
{
    protected $signature = 'search:reindex';

    protected $description = 'Apply search index settings and import every searchable model';

    public const MODELS = [
        Trail::class, Restaurant::class, Location::class, Event::class, Accommodation::class,
        Post::class, Guide::class, ResourceText::class, OpenAIPrompt::class,
    ];

    public function handle(): int
    {
        $this->call('scout:sync-index-settings');
        foreach (self::MODELS as $model) {
            $this->call('scout:import', ['model' => $model]);
        }
        return self::SUCCESS;
    }
}
