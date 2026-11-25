<?php

namespace Tests\Feature\Chat;

use App\Chat\Models\ChatModel;
use App\Chat\Models\ScriptedModel;
use App\Models\Area;
use App\Models\ResourceFact;
use App\Models\TrailGeoJson;
use App\Models\TrailProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeoJson;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class ChatCommandsTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seedKanabFixtures();
    }

    public function test_trails_profile_computes_and_skips_unchanged_tracks(): void
    {
        $id = $this->fixtureTrailIds[Area::KANAB][1];
        TrailGeoJson::create(['trail_id' => $id, 'geojson' => GeoJson::line(37.0, -112.5, 37.02, -112.5, 100, 1500, 1600)]);

        $this->artisan('trails:profile')->expectsOutputToContain('1 computed, 0 unchanged')->assertSuccessful();
        $this->assertEqualsWithDelta(328, TrailProfile::find($id)->gain_ft, 5); // 100 m
        $this->artisan('trails:profile')->expectsOutputToContain('0 computed, 1 unchanged')->assertSuccessful();
    }

    public function test_extract_facts_stores_valid_claims_for_mentioned_trails_only(): void
    {
        $trail = $this->insertTrail(['name' => 'Kanarra Creek Trail', 'area_id' => Area::ZION]);
        $blog = DB::table('blogs')->insertGetId(['name' => 'B', 'url' => 'https://b.test']);
        $post = DB::table('blog_posts')->insertGetId(['blog_id' => $blog, 'post_id' => 9, 'title' => 'Waterfalls near Zion',
            'content' => '<p>Kanarra Creek Trail near Zion means walking in the creek. Wear water shoes.</p>', 'posted_at' => '2025-06-01',
            'raw_data' => json_encode(['link' => 'https://b.test/kanarra'])]);

        $model = new ScriptedModel([ScriptedModel::say(json_encode(['facts' => [
            ['record_id' => $trail, 'topic' => 'gear', 'claim' => 'Water shoes help; most of the route is in the creek.'],
            ['record_id' => 99999, 'topic' => 'gear', 'claim' => 'Not a mentioned trail.'],
            ['record_id' => $trail, 'topic' => 'astrology', 'claim' => 'Bad topic.'],
        ]]))]);
        $this->app->instance(ChatModel::class, $model);

        $this->artisan('chat:extract-facts', ['--post' => [$post]])->expectsOutputToContain('Stored 1 facts.')->assertSuccessful();
        $fact = ResourceFact::sole();
        $this->assertSame([$trail, 'gear', 'https://b.test/kanarra', '2025-06-01'], [$fact->resource_id, $fact->topic, $fact->source_url, $fact->source_date->toDateString()]);
        $this->assertStringContainsString("- {$trail}: Kanarra Creek Trail", $model->requests[0]['transcript'][0]['text']);

        // Facts now lead trail_knowledge answers.
        $knowledge = app(\App\Chat\Data\Knowledge::class)->about('trails', $trail, 'Kanarra Creek Trail', Area::ZION, 'gear');
        $this->assertSame('Water shoes help; most of the route is in the creek.', $knowledge['facts'][0]['claim']);
    }

    public function test_chat_eval_runs_templates_and_writes_a_report(): void
    {
        config(['chat.eval.report_dir' => storage_path('framework/testing/chat-eval')]);
        File::deleteDirectory(config('chat.eval.report_dir'));
        $this->app->instance(ChatModel::class, new ScriptedModel([
            ScriptedModel::call('trail_details', ['trail_id' => $this->fixtureTrailIds[Area::KANAB][0]]),
            ScriptedModel::say('Answer one.'),
            ScriptedModel::say("You're welcome!"),
        ]));

        $this->artisan('chat:eval', ['--ids' => '7,300'])->expectsOutputToContain('Report:')->assertSuccessful();

        $report = json_decode(File::get(File::glob(config('chat.eval.report_dir') . '/*.json')[0]), true);
        $this->assertSame([7, 300], array_column($report['results'], 'id'));
        $this->assertSame(['trail_details'], $report['results'][0]['used_tools']);
        $this->assertSame(0.33, $report['results'][0]['tool_recall']);
        $this->assertTrue($report['results'][1]['pass']);
    }
}
