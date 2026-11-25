<?php

namespace Tests\Data;

use App\Chat\ChatContext;
use App\Chat\Data\TrailProfiler;
use App\Chat\Tools\ToolRegistry;
use App\Models\TrailGeoJson;
use App\Models\TrailProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * The chat tools against the real dataset: the questions that motivated them should get sensible evidence.
 */
class ChatDataTest extends DataTestCase
{
    private function tool(string $name, array $args): array
    {
        $context = new ChatContext(CarbonImmutable::parse('2026-07-10 09:00', 'America/Denver'));
        return ToolRegistry::default()->get($name)->handle($args, $context)->data;
    }

    private function trailId(string $name): int
    {
        return (int) \DB::table('trails')->where('name', $name)->where('is_usable', 1)->value('id');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_every_track_with_elevation_gets_a_profile(): void
    {
        $profiler = TrailProfiler::fromConfig();
        $failed = [];
        foreach (TrailGeoJson::all(['trail_id', 'geojson']) as $row) {
            if (!$profiler->profile($row->geojson)) {
                $failed[] = $row->trail_id;
            }
        }
        $this->assertSame([], $failed);
    }

    public function test_k_trail_finds_k_hill(): void
    {
        $this->assertSame('K-Hill Trail', $this->tool('search_trails', ['query' => 'K Trail'])['matches'][0]['name']);
    }

    public function test_overlook_is_ambiguous_and_lists_several_areas(): void
    {
        $result = $this->tool('search_trails', ['query' => 'Overlook Trail']);
        $this->assertTrue($result['ambiguous']);
        $this->assertGreaterThanOrEqual(2, count(array_unique(array_column($result['matches'], 'area'))));
    }

    public function test_misspelled_angels_landing(): void
    {
        $names = array_column($this->tool('search_trails', ['query' => 'angles landing'])['matches'], 'name');
        $this->assertContains("Angel's Landing Trail", $names);
    }

    public function test_navajo_loop_has_a_steep_section_a_five_year_old_should_know_about(): void
    {
        $result = $this->tool('assess_for_hiker', ['trail_id' => $this->trailId('Navajo Loop'), 'hikers' => [['age' => 5]]]);
        $this->assertNotSame('good', $result['group_verdict']);
        $checks = array_column($result['hikers'][0]['issues'], 'check');
        $this->assertContains('steepest_tenth_mile', $checks);
        $this->assertNotEmpty($result['steepest_sections']);
    }

    public function test_canyon_overlook_is_fine_for_a_seven_year_old_by_grade(): void
    {
        $result = $this->tool('assess_for_hiker', ['trail_id' => $this->trailId('Canyon Overlook Trail'), 'hikers' => [['age' => 7]]]);
        $grades = array_intersect(['steepest_tenth_mile', 'steepest_pitch', 'distance', 'total_climb'], array_column($result['hikers'][0]['issues'], 'check'));
        $this->assertSame([], array_values($grades));
    }

    public function test_kanarra_gear_comes_from_blogs(): void
    {
        $data = $this->tool('trail_knowledge', ['trail_id' => $this->trailId('Kanarra Creek Trail'), 'topic' => 'gear']);
        $this->assertNotEmpty($data['blog_excerpts']);
        $this->assertMatchesRegularExpression('/shoes|water|wear/i', implode(' ', array_column($data['blog_excerpts'], 'text')));
    }

    public function test_profiles_climb_totals_are_close_to_listed_climbs(): void
    {
        $diffs = TrailProfile::join('trails', 'trails.id', '=', 'trail_profiles.trail_id')
            ->where('trails.incline_ft', '>', 150)->get(['trail_profiles.gain_ft', 'trails.incline_ft'])
            ->map(fn($r) => ($r->gain_ft - $r->incline_ft) / $r->incline_ft)->sort()->values();
        if ($diffs->isEmpty()) {
            $this->markTestSkipped('Run php artisan trails:profile on the dataset first.');
        }
        $this->assertEqualsWithDelta(0, $diffs[intdiv($diffs->count(), 2)], 0.1, 'Median difference from listed climb');
    }
}
