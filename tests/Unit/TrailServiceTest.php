<?php

namespace Tests\Unit;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Trail;
use App\Services\TrailService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\GeoJson;
use Tests\TestCase;

class TrailServiceTest extends TestCase
{
    private TrailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrailService();
    }

    public function test_haversine_matches_known_distance(): void
    {
        // Kanab to Zion's south entrance is ~33 miles as the crow flies.
        $miles = $this->service->haversine(37.0475, -112.526, 37.2982, -113.026);
        $this->assertEqualsWithDelta(32.5, $miles, 1.5);
        $this->assertSame(0.0, $this->service->haversine(37.0, -112.0, 37.0, -112.0));
    }

    public static function difficulties(): array
    {
        return [
            ['Easy', 1.5, 1.2],
            ['Easy/Intermediate', 1.4, 1.1],
            ['Intermediate', 1.3, 1.0],
            ['Intermediate/Difficult', 1.2, 0.9],
            ['Difficult', 1.1, 0.8],
            ['Very Difficult', 0.9, 0.7],
            [null, 1.3, 1.0],
            ['Something new', 1.3, 1.0],
        ];
    }

    #[DataProvider('difficulties')]
    public function test_speed_by_difficulty(?string $difficulty, float $mph, float $multiplier): void
    {
        $this->assertSame($mph, $this->service->calculateMphByDifficulty($difficulty));
        $this->assertSame($multiplier, $this->service->getMphMultiplierForDifficulty($difficulty));
    }

    public function test_harder_trails_take_longer_for_the_same_distance(): void
    {
        $easy = $this->service->calculateHikingTime(6, null, 'Easy');
        $hard = $this->service->calculateHikingTime(6, null, 'Very Difficult');
        $this->assertSame(4.0, $easy);
        $this->assertGreaterThan($easy, $hard);
        $this->assertSame(3.0, $this->service->calculateHikingTime(6, 2.0));
    }

    public function test_flat_trail_estimate_is_close_to_distance_over_speed(): void
    {
        $geoJson = GeoJson::line(37.0, -112.5, 37.0 + 3 / 69.0, -112.5, 60, 1500, 1500); // ~3 miles, flat
        $stats = $this->service->analyzeTrail($geoJson, 2.0, ActivityType::HIKING, 2);

        $this->assertEqualsWithDelta(3.0, $stats['total_dist'], 0.05);
        // Distance fatigue makes it a bit slower than 3 mi / 2 mph, but not wildly so.
        $this->assertGreaterThan(1.5, $stats['total_hours']);
        $this->assertLessThan(3.0, $stats['total_hours']);
    }

    public function test_climbing_is_slower_than_descending_the_same_path(): void
    {
        $up = GeoJson::line(37.0, -112.5, 37.04, -112.5, 60, 1500, 2100);
        $down = GeoJson::line(37.0, -112.5, 37.04, -112.5, 60, 2100, 1500);

        $upHours = $this->service->analyzeTrail($up, 2.0, ActivityType::HIKING, 3)['total_hours'];
        $downHours = $this->service->analyzeTrail($down, 2.0, ActivityType::HIKING, 3)['total_hours'];

        $this->assertGreaterThan($downHours, $upHours);
    }

    public function test_bicycling_is_faster_than_hiking(): void
    {
        $geoJson = GeoJson::line(37.0, -112.5, 37.05, -112.5, 60, 1500, 1500);
        $hike = $this->service->analyzeTrail($geoJson, 2.0, ActivityType::HIKING, 3);
        $bike = $this->service->analyzeTrail($geoJson, 8.0, ActivityType::BICYCLING, 3);
        $this->assertLessThan($hike['total_hours'], $bike['total_hours']);
    }

    public function test_hourly_markers_follow_the_path(): void
    {
        $geoJson = GeoJson::line(37.0, -112.5, 37.1, -112.5, 200, 1500, 1500); // ~6.9 miles
        $stats = $this->service->analyzeTrail($geoJson, 2.0, ActivityType::HIKING, 2);

        $hours = array_keys($stats['time_hours']);
        $this->assertSame(range(0, count($hours) - 1), $hours, 'One marker per elapsed hour, starting at hour 0.');
        $this->assertGreaterThanOrEqual(floor($stats['total_hours']), max($hours));
        $latitudes = array_column($stats['time_hours'], 0);
        $sorted = $latitudes;
        sort($sorted);
        $this->assertSame($sorted, $latitudes, 'Markers advance along the trail.');
    }

    public function test_unknown_activity_type_is_rejected(): void
    {
        $this->expectException(\Exception::class);
        $this->service->analyzeTrail(GeoJson::line(37.0, -112.5, 37.01, -112.5, 5, 0, 0), 2.0, 'kayaking');
    }

    public function test_long_trails_start_in_the_morning(): void
    {
        $trail = new Trail(['area_id' => Area::KANAB, 'average_hours' => 6.5, 'difficulty' => 'Difficult']);
        $this->assertSame(Period::MORNING, $this->service->getBestPeriodOfDayForTrail($trail));
    }

    public function test_medium_zion_trails_start_in_the_morning_to_beat_the_crowds(): void
    {
        $trail = new Trail(['area_id' => Area::ZION, 'average_hours' => 2.5, 'difficulty' => 'Intermediate']);
        $this->assertSame(Period::MORNING, $this->service->getBestPeriodOfDayForTrail($trail));
    }

    public function test_medium_trails_elsewhere_start_in_the_morning_or_afternoon(): void
    {
        $trail = new Trail(['area_id' => Area::KANAB, 'average_hours' => 4, 'difficulty' => 'Intermediate']);
        for ($i = 0; $i < 20; $i++) {
            $this->assertContains($this->service->getBestPeriodOfDayForTrail($trail), [Period::MORNING, Period::AFTERNOON]);
        }
    }

    public function test_trail_model_keeps_constructor_attributes(): void
    {
        $trail = new Trail(['name' => 'Wire Pass', 'distance_miles' => 3.4]);
        $this->assertSame('Wire Pass', $trail->name);
        $this->assertSame(3.4, $trail->distance_miles);
        $this->assertInstanceOf(TrailService::class, $trail->trailService);
    }

    public function test_trail_difficulty_helpers(): void
    {
        $this->assertTrue((new Trail(['difficulty' => 'Easy/Intermediate']))->isEasy());
        $this->assertTrue((new Trail(['difficulty' => 'Easy/Intermediate']))->isIntermediate());
        $this->assertFalse((new Trail(['difficulty' => 'Intermediate']))->isDifficult());
        $this->assertTrue((new Trail(['difficulty' => 'Very Difficult']))->isDifficult());
    }

    public function test_hiking_estimate_does_not_depend_on_gps_point_density(): void
    {
        // The same 3.5-mile climb, sampled every ~0.35 mi vs every ~18 ft.
        $sparse = $this->service->analyzeTrail(GeoJson::line(37.0, -112.5, 37.05, -112.5, 10, 1500, 1800), 2.0, ActivityType::HIKING, 2);
        $dense = $this->service->analyzeTrail(GeoJson::line(37.0, -112.5, 37.05, -112.5, 1000, 1500, 1800), 2.0, ActivityType::HIKING, 2);

        $this->assertEqualsWithDelta($sparse['total_hours'], $dense['total_hours'], 0.05 * $sparse['total_hours']);
        $this->assertEqualsWithDelta(984, $sparse['elevation_gain_ft'], 2, '300 m of climbing.');
    }

    public function test_gps_jitter_does_not_inflate_the_estimate(): void
    {
        $smooth = json_decode(GeoJson::line(37.0, -112.5, 37.05, -112.5, 1000, 1500, 1500), true);
        $noisy = $smooth;
        foreach ($noisy['features'][0]['geometry']['coordinates'] as $i => &$point) {
            $point[2] += ($i % 2 ? 3 : -3); // ±3 m of elevation noise on every point
        }
        $smoothHours = $this->service->analyzeTrail(json_encode($smooth), 2.0, ActivityType::HIKING, 2)['total_hours'];
        $noisyHours = $this->service->analyzeTrail(json_encode($noisy), 2.0, ActivityType::HIKING, 2)['total_hours'];
        $this->assertEqualsWithDelta($smoothHours, $noisyHours, 0.05 * $smoothHours);
    }

    public function test_multilinestring_tracks_are_read(): void
    {
        $line = json_decode(GeoJson::line(37.0, -112.5, 37.02, -112.5, 10, 1500, 1500), true)['features'][0]['geometry']['coordinates'];
        $multi = ['type' => 'Feature', 'geometry' => ['type' => 'MultiLineString', 'coordinates' => [$line, $line]]];
        $this->assertCount(22, $this->service->extractTrailPoints($multi));
        $this->expectException(\InvalidArgumentException::class);
        $this->service->analyzeTrail(['type' => 'FeatureCollection', 'features' => []], 2.0);
    }

    public function test_short_easy_trails_are_evening_hikes(): void
    {
        $easy = new Trail(['area_id' => Area::KANAB, 'average_hours' => 0.5, 'difficulty' => 'Easy']);
        $this->assertSame(Period::EVENING, $this->service->getBestPeriodOfDayForTrail($easy));

        $steep = new Trail(['area_id' => Area::KANAB, 'average_hours' => 1.0, 'difficulty' => 'Difficult']);
        for ($i = 0; $i < 10; $i++) {
            $this->assertContains($this->service->getBestPeriodOfDayForTrail($steep), [Period::MORNING, Period::AFTERNOON]);
        }
    }
}
