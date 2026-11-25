<?php

namespace Tests\Unit\Chat;

use App\Chat\Data\TrailProfiler;
use PHPUnit\Framework\TestCase;

class TrailProfilerTest extends TestCase
{
    /** Miles of latitude per degree, so tracks can be laid out in miles. */
    private const DEG_PER_MILE = 1 / 69.05;

    /**
     * A northbound track built from segments of [miles, rise_ft]: flat, then a hill, then flat again, etc.
     */
    private static function track(array $segments, float $startFt = 5000, int $pointsPerMile = 200): array
    {
        $coordinates = [];
        $lat = 37.0;
        $ft = $startFt;
        $coordinates[] = [-112.5, $lat, $ft / 3.28084];
        foreach ($segments as [$miles, $rise]) {
            $n = max(1, (int) round($miles * $pointsPerMile));
            for ($i = 1; $i <= $n; $i++) {
                $lat += $miles / $n * self::DEG_PER_MILE;
                $ft += $rise / $n;
                $coordinates[] = [-112.5, $lat, $ft / 3.28084];
            }
        }
        return ['type' => 'FeatureCollection', 'features' => [['type' => 'Feature', 'properties' => [], 'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates]]]];
    }

    public function test_finds_one_steep_hill_on_an_otherwise_flat_trail(): void
    {
        // 1 mile flat, 0.2 mile at 30% (317 ft), 1 mile flat.
        $profile = (new TrailProfiler())->profile(self::track([[1.0, 0], [0.2, 0.2 * 5280 * 0.30], [1.0, 0]]));

        $this->assertEqualsWithDelta(2.2, $profile['track_miles'], 0.02);
        $this->assertEqualsWithDelta(317, $profile['gain_ft'], 15);
        $this->assertSame(0, $profile['loss_ft']);
        $this->assertEqualsWithDelta(30, $profile['steepest_sustained']['grade_pct'], 2.5);
        $this->assertSame('up', $profile['steepest_sustained']['direction']);
        $this->assertEqualsWithDelta(1.0, $profile['steepest_sustained']['starts_at_mile'], 0.1);

        $this->assertCount(1, $profile['very_steep_sections']);
        $section = $profile['very_steep_sections'][0];
        $this->assertEqualsWithDelta(1.0, $section['from_mile'], 0.06);
        $this->assertEqualsWithDelta(1.2, $section['to_mile'], 0.06);
        $this->assertGreaterThan(0.85, $profile['gentle_share']);
        $this->assertFalse($profile['returns_to_start']);
    }

    public function test_gentle_trail_has_no_steep_sections(): void
    {
        $profile = (new TrailProfiler())->profile(self::track([[3.0, 3 * 5280 * 0.04]]));

        $this->assertSame([], $profile['steep_sections']);
        $this->assertEqualsWithDelta(4, $profile['steepest_quarter_mile']['grade_pct'], 0.5);
        $this->assertEquals(1.0, $profile['gentle_share']);
        $this->assertEqualsWithDelta(634, $profile['longest_climb']['gain_ft'], 20);
    }

    public function test_gps_jitter_does_not_create_fake_climbs(): void
    {
        $track = self::track([[2.0, 0]]);
        foreach ($track['features'][0]['geometry']['coordinates'] as $i => &$c) {
            $c[2] += ($i % 2 ? 0.6 : -0.6); // ±2 ft noise on every point
        }
        $profile = (new TrailProfiler())->profile($track);

        $this->assertLessThan(15, $profile['gain_ft']);
        $this->assertSame([], $profile['steep_sections']);
    }

    public function test_out_and_back_track_returns_to_start(): void
    {
        $out = self::track([[1.0, 300]]);
        $coordinates = $out['features'][0]['geometry']['coordinates'];
        $back = array_reverse(array_slice($coordinates, 0, -1));
        $out['features'][0]['geometry']['coordinates'] = [...$coordinates, ...$back];

        $profile = (new TrailProfiler())->profile($out);
        $this->assertTrue($profile['returns_to_start']);
        $this->assertEqualsWithDelta(2.0, $profile['track_miles'], 0.02);
        $this->assertEqualsWithDelta(300, $profile['gain_ft'], 10);
        $this->assertEqualsWithDelta(300, $profile['loss_ft'], 10);
    }

    public function test_reads_multilinestrings_and_skips_points_without_elevation(): void
    {
        $geojson = ['type' => 'MultiLineString', 'coordinates' => [
            [[-112.5, 37.0, 1500], [-112.5, 37.005, 1510], [-112.5, 37.006]],
            [[-112.5, 37.01, 1520], [-112.5, 37.015, 1530]],
        ]];
        $this->assertCount(4, TrailProfiler::points($geojson));
        $this->assertNotNull((new TrailProfiler())->profile($geojson));
    }

    public function test_unusable_tracks_return_null(): void
    {
        $profiler = new TrailProfiler();
        $this->assertNull($profiler->profile(['type' => 'FeatureCollection', 'features' => []]));
        $this->assertNull($profiler->profile(['type' => 'LineString', 'coordinates' => [[-112.5, 37.0], [-112.5, 37.1]]]));
        $this->assertNull($profiler->profile('not json'));
    }
}
