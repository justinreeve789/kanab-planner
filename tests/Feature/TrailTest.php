<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Services\TrailService;
use Tests\TestCase;
use Throwable;

class TrailTest extends TestCase
{
    private $trailService;

    public function setUp(): void
    {
        parent::setUp();
        $this->trailService = new TrailService();
    }

    /**
     * Using TrailService:analyzeTrail(), check that the estimates are reasonably close to what's stored in trails.average_hours.
     *
     * @return void
     * @throws \Exception
     */
    public function test_calculated_hiking_estimates_are_close_to_trail_duration_estimates(): void
    {
        $trails = Trail::with('geoJson')->whereHas('geoJson')->where('trails.average_hours', '>=', '1')->get();
        foreach ($trails as $trail) {
            $statsLow = $this->trailService->analyzeTrail($trail->geoJson->geojson, 3, ActivityType::HIKING, 2);
            $statsHigh = $this->trailService->analyzeTrail($trail->geoJson->geojson, 1.25, ActivityType::HIKING, 2);
            $message = 'Trail ' . $trail->id . ' has average hours of ' . $trail->average_hours .
                '. Calculated range is ' . $statsLow['total_hours'] . ' and ' . $statsHigh['total_hours'] . '.';
            $this->assertGreaterThan($statsLow['total_hours'], $trail->average_hours, $message);
            $this->assertLessThan($statsHigh['total_hours'], $trail->average_hours, $message);

            $statsAverage = ($statsLow['total_hours'] + $statsHigh['total_hours']) / 2;
            $allowedDeviation = $trail->average_hours * 0.25;
            $actualDeviation = abs($statsAverage - $trail->average_hours);
            $message = 'Trail ' . $trail->id . ' allowed deviation is ' . $allowedDeviation .
                '. Actual deviation is ' . $actualDeviation;
            $this->assertLessThan($allowedDeviation, $actualDeviation, $message);
        }
    }
}
