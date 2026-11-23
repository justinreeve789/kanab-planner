<?php

namespace Tests\Unit;

use App\Services\LocationService;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    private LocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocationService();
    }

    public function test_driving_distance_pads_straight_line_distance(): void
    {
        // Kanab → Page is ~60 straight-line miles; driving is padded 12.5%.
        $miles = $this->service->estimateDrivingDistance(37.0475, -112.526, 36.9147, -111.455);
        $this->assertEqualsWithDelta(60.0 * 1.125, $miles, 3);
        $this->assertSame(0.0, $this->service->estimateDrivingDistance(37.0, -112.0, 37.0, -112.0));
    }

    public function test_driving_hours_assume_thirty_mph(): void
    {
        $hours = $this->service->estimateDrivingHours(37.0475, -112.526, 36.9147, -111.455);
        $this->assertEqualsWithDelta(2.0, $hours, 0.15);
    }

    public function test_popular_times_parses_every_day_including_sunday(): void
    {
        $output = implode("\n", [
            'Day: Sunday, Popular Times: [0, 0, 10, 45, 100]',
            'Day: Monday, Popular Times: [5, 5]',
            'Day: Saturday, Popular Times: [1,2,3]',
            'Some other log line',
        ]);

        $parsed = $this->service->parsePopularTimes($output);

        $this->assertSame([0, 1, 6], array_keys($parsed));
        $this->assertSame(['Sunday', [0, 0, 10, 45, 100]], $parsed[0]);
        $this->assertSame(['Saturday', [1, 2, 3]], $parsed[6]);
    }

    public function test_popular_times_ignores_unknown_days_and_garbage(): void
    {
        $this->assertSame([], $this->service->parsePopularTimes('Day: Funday, Popular Times: [1, 2]'));
        $this->assertSame([], $this->service->parsePopularTimes(''));
    }
}
