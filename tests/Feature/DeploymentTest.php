<?php

namespace Tests\Feature;

use App\Console\Commands\ReindexSearch;
use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Tests\Support\RefreshTestDatabase;
use Tests\TestCase;

/**
 * What docker/entrypoint.sh and docker-compose.yml rely on.
 */
class DeploymentTest extends TestCase
{
    use RefreshTestDatabase;

    public function test_health_check(): void
    {
        $this->get('/up')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_has_data_tells_the_entrypoint_whether_to_seed(): void
    {
        $this->artisan('app:has-data')->expectsOutput('The dataset is empty.')->assertFailed();

        DB::table('trails')->insert(['name' => 'Wire Pass', 'area_id' => Area::KANAB, 'source_type' => 'fixture', 'source_id' => '1', 'is_usable' => 1]);
        $this->artisan('app:has-data')->expectsOutput('The dataset is loaded.')->assertSuccessful();
    }

    public function test_every_searchable_model_is_reindexed(): void
    {
        foreach (ReindexSearch::MODELS as $model) {
            $this->assertContains(Searchable::class, class_uses_recursive($model), "{$model} isn't searchable.");
        }
        $indexes = array_keys(config('scout.meilisearch.index-settings'));
        foreach (['trails', 'restaurants', 'locations', 'events', 'accommodations'] as $index) {
            $this->assertContains($index, $indexes);
        }
    }
}
