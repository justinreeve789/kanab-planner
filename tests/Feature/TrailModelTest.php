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

    public function test_longer_steeper_trails_are_more_strenuous(): void
    {
        $hiking = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $data = new DataService();
        $stroll = new Trail(['distance_miles' => 1.0, 'incline_ft' => 20, 'decline_ft' => 20, 'average_grade_pt' => 0.01, 'max_grade_pt' => 0.03, 'difficulty' => 'Intermediate', 'average_hours' => 0.5]);
        $climb = new Trail(['distance_miles' => 9.0, 'incline_ft' => 2800, 'decline_ft' => 300, 'average_grade_pt' => 0.12, 'max_grade_pt' => 0.35, 'difficulty' => 'Intermediate', 'average_hours' => 0.5]);

        $strollScore = $data->calculateStrenuousnessForTrail($stroll, $hiking);
        $climbScore = $data->calculateStrenuousnessForTrail($climb, $hiking);
        $this->assertGreaterThan($strollScore + 0.4, $climbScore);
        foreach ([$strollScore, $climbScore] as $score) {
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(1, $score);
        }
    }

    public function test_strenuousness_never_drops_as_a_trail_gets_longer_or_steeper(): void
    {
        $hiking = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $data = new DataService();
        $previous = -1;
        foreach ([[1, 100], [3, 400], [6, 1200], [10, 2500], [16, 4500]] as [$miles, $climb]) {
            $score = $data->calculateStrenuousnessForTrail(new Trail(['distance_miles' => $miles, 'incline_ft' => $climb, 'difficulty' => 'Intermediate']), $hiking);
            $this->assertGreaterThan($previous, $score);
            $previous = $score;
        }

        $easy = $data->calculateStrenuousnessForTrail(new Trail(['distance_miles' => 4, 'incline_ft' => 600, 'difficulty' => 'Easy']), $hiking);
        $hard = $data->calculateStrenuousnessForTrail(new Trail(['distance_miles' => 4, 'incline_ft' => 600, 'difficulty' => 'Very Difficult']), $hiking);
        $this->assertGreaterThan($easy, $hard);
    }

    public function test_trails_without_stats_use_the_activity_default(): void
    {
        $hiking = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $this->assertEquals(0.6, (new DataService())->calculateStrenuousnessForTrail(new Trail([]), $hiking));
    }

    public function test_trails_without_resource_data_can_still_be_picked(): void
    {
        $orphan = $this->insertTrail(['name' => 'Unscored Zion Trail', 'area_id' => Area::ZION, 'difficulty' => 'Intermediate', 'distance_miles' => 4.0, 'average_hours' => 2.5]);
        DB::table('trails')->where('area_id', Area::ZION)->where('id', '!=', $orphan)->update(['is_usable' => 0]);

        $itinerary = new \App\Models\Itinerary\Itinerary();
        $trail = (new TrailService())->getRandomTrailForPeriod(Area::ZION, \App\Models\Itinerary\Period::MORNING, $itinerary);
        $this->assertSame($orphan, $trail?->id);
    }

    public function test_update_data_command_scores_every_trail(): void
    {
        DB::table('resource_data')->where('resource_table', 'trails')->delete();
        $this->artisan('update:data', ['--trails' => true])->assertSuccessful();

        $this->assertSame(Trail::count(), DB::table('resource_data')->where('resource_table', 'trails')->count());
        $long = DB::table('resource_data')->where('resource_id', $this->fixtureTrailIds[Area::ZION][4])->value('strenuousness');
        $short = DB::table('resource_data')->where('resource_id', $this->fixtureTrailIds[Area::ZION][0])->value('strenuousness');
        $this->assertGreaterThan($short, $long);
    }
}
