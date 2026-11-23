<?php

namespace Tests\Feature;

use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Tests\Support\GeoJson;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    public function test_lists_areas(): void
    {
        $this->getJson('/api/v1/area')
            ->assertOk()
            ->assertJsonCount(7)
            ->assertJsonFragment(['id' => Area::ZION, 'slug' => 'zion']);
    }

    public function test_gets_one_area_or_404(): void
    {
        $this->getJson('/api/v1/area/' . Area::KANAB)->assertOk()->assertJsonPath('short_name', 'Kanab');
        $this->getJson('/api/v1/area/999')->assertNotFound();
    }

    public function test_area_trails_sort_ascending_and_descending(): void
    {
        $asc = $this->getJson('/api/v1/area/' . Area::ZION . '/trail?sort=distance_miles')->assertOk()->json();
        $desc = $this->getJson('/api/v1/area/' . Area::ZION . '/trail?sort=-distance_miles')->assertOk()->json();

        $ascMiles = array_map('floatval', array_column($asc, 'distance_miles'));
        $this->assertSame([0.8, 2.5, 4.0, 7.0, 14.0], $ascMiles);
        $this->assertSame(array_reverse(array_column($asc, 'id')), array_column($desc, 'id'));
        $this->assertNotContains($this->mergedAwayTrailId, array_column($asc, 'id'), 'Merged-away trails are hidden.');
    }

    public function test_area_trails_sort_rejects_sql(): void
    {
        foreach (['(SELECT SLEEP(1))', 'name; DROP TABLE trails', 'id,(select 1)', '-raw_data'] as $sort) {
            $this->getJson('/api/v1/area/' . Area::ZION . '/trail?sort=' . urlencode($sort))
                ->assertStatus(422)
                ->assertJsonValidationErrors('sort');
        }
        $this->assertSame(7 * count(self::FIXTURE_TRAIL_LADDER) + 2, DB::table('trails')->count());
    }

    public function test_area_restaurants_and_locations(): void
    {
        $restaurants = $this->getJson('/api/v1/area/' . Area::BRYCE . '/restaurant')->assertOk()->json();
        $this->assertCount(self::FIXTURE_RESTAURANTS_PER_AREA, $restaurants);
        $this->assertEqualsCanonicalizing($this->fixtureRestaurantIds[Area::BRYCE], array_column($restaurants, 'id'));

        $this->getJson('/api/v1/area/' . Area::BRYCE . '/location')
            ->assertOk()
            ->assertJsonCount(self::FIXTURE_RESTAURANTS_PER_AREA);

        $this->getJson('/api/v1/area/' . Area::BRYCE . '/event')->assertOk()->assertJsonCount(0);
    }

    public function test_activity_types_only_include_selectable_ones(): void
    {
        $names = array_column($this->getJson('/api/v1/activity_type')->assertOk()->json(), 'name');
        $this->assertContains('hiking', $names);
        $this->assertNotContains('checking-in', $names);
        $this->assertNotContains('cooking', $names);
    }

    public function test_feature_flags(): void
    {
        $this->getJson('/api/v1/feature')
            ->assertOk()
            ->assertJsonFragment(['feature' => 'show-assistant', 'enabled' => true]);
    }

    public function test_trail_detail_hides_raw_source_data(): void
    {
        $id = $this->fixtureTrailIds[Area::KANAB][2];
        $this->getJson("/api/v1/trail/{$id}")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('area.id', Area::KANAB)
            ->assertJsonMissingPath('raw_data')
            ->assertJsonMissingPath('source_id');
    }

    public function test_trail_geojson_analyze_and_download(): void
    {
        $id = $this->fixtureTrailIds[Area::KANAB][2];
        $geoJson = GeoJson::line(37.05, -112.52, 37.08, -112.52, 40, 1500, 1800);
        DB::table('trail_geojson')->insert(['trail_id' => $id, 'geojson' => $geoJson]);

        $this->get("/api/v1/trail/{$id}/geojson")->assertOk()->assertSee('LineString', false);

        $analysis = $this->getJson("/api/v1/trail/{$id}/geojson/analyze?average_speed=2")->assertOk()->json();
        $this->assertGreaterThan(0, $analysis['total_hours']);
        $this->assertEqualsWithDelta(2.07, $analysis['total_dist'], 0.1);

        $this->getJson("/api/v1/trail/{$id}/geojson/analyze?average_speed=0")->assertStatus(422);

        $download = $this->get("/api/v1/trail/{$id}/geojson/download")->assertOk();
        $this->assertStringContainsString("{$id}.geojson", $download->headers->get('content-disposition'));
        $this->assertSame($geoJson, $download->streamedContent());

        $this->get('/api/v1/trail/999999/geojson/download')->assertNotFound();
    }

    public function test_featured_works_without_area_ids_and_404s_unknown_tables(): void
    {
        $this->getJson('/api/v1/featured/trails')->assertOk();
        $this->getJson('/api/v1/featured/trails?area_ids[]=' . Area::ZION)->assertOk();
        $this->getJson('/api/v1/featured/users')->assertNotFound();
    }

    public function test_unknown_itinerary_day_and_activity(): void
    {
        $this->getJson('/api/v1/itinerary/00000000-0000-0000-0000-000000000000')->assertStatus(422);
        $this->getJson('/api/v1/day/00000000-0000-0000-0000-000000000000')->assertNotFound();
        $this->getJson('/api/v1/activity/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }
}
