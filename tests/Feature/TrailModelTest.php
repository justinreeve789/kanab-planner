<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Trail;
use App\Services\DataService;
use App\Services\TrailService;
use Illuminate\Support\Facades\DB;
use Tests\Support\GeoJson;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class TrailModelTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    public function test_unusable_trails_arent_retrieved(): void
    {
        $trails = Trail::all();
        $this->assertCount(count($this->usableTrailIds()), $trails);
        foreach ($trails as $trail) {
            $this->assertTrue((bool) $trail->is_usable, "Trail {$trail->id} is not usable.");
        }
        $this->assertNull(Trail::find($this->unusableTrailId));
        $this->assertNotNull(Trail::withoutGlobalScopes()->find($this->unusableTrailId));
    }

    public function test_resource_data_is_exposed_as_attributes(): void
    {
        $trail = Trail::find($this->fixtureTrailIds[Area::ZION][4]);
        $this->assertEquals(0.90, $trail->adventurousness);
        $this->assertEquals(0.95, $trail->strenuousness);
        $this->assertSame('trail', $trail->type);
    }

    public function test_estimated_hours_come_from_the_trails_geojson(): void
    {
        $trail = Trail::find($this->fixtureTrailIds[Area::KANAB][2]);
        $geoJson = GeoJson::line(37.05, -112.52, 37.11, -112.52, 80, 1500, 1500); // ~4.1 flat miles
        DB::table('trail_geojson')->insert(['trail_id' => $trail->id, 'geojson' => $geoJson]);

        $service = new TrailService();
        $hours = $trail->getEstimatedHours($service, 2.0);
        $this->assertSame($service->analyzeTrail($geoJson, 2.0)['total_hours'], $hours);
        $this->assertGreaterThan(1.5, $hours);
    }

    public function test_estimated_hours_without_geojson_fall_back_to_default(): void
    {
        $trail = Trail::find($this->fixtureTrailIds[Area::KANAB][0]);
        $this->assertSame(Trail::DEFAULT_AVERAGE_HOURS, $trail->getEstimatedHours(new TrailService(), 2.0));
    }

    public function test_listed_average_hours_take_precedence_over_estimates(): void
    {
        $trail = Trail::find($this->fixtureTrailIds[Area::KANAB][3]);
        $this->assertEquals(4.0, $trail->getAverageHours());
    }

    public function test_unique_and_long_trails_rate_as_more_adventurous(): void
    {
        $hiking = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $data = new DataService();

        $short = new Trail(['distance_miles' => 2.0, 'unique' => 0]);
        $long = new Trail(['distance_miles' => 12.0, 'unique' => 0]);
        $iconic = new Trail(['distance_miles' => 2.0, 'unique' => 1]);

        $base = $data->calculateAdventurousnessForTrail($short, $hiking);
        $this->assertGreaterThan($base, $data->calculateAdventurousnessForTrail($long, $hiking));
        $this->assertGreaterThan($base, $data->calculateAdventurousnessForTrail($iconic, $hiking));
        $this->assertLessThanOrEqual(1, $data->calculateAdventurousnessForTrail(new Trail(['distance_miles' => 40, 'unique' => 1]), $hiking));
    }
}
