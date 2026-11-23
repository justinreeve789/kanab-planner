<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
        $this->restaurant = Restaurant::find($this->fixtureRestaurantIds[Area::KANAB][0]);
    }

    public function test_restaurant_returns_average_hours_based_on_busyness(): void
    {
        // 2027-06-06 is a Sunday (day_of_week 0); busyness is keyed by the restaurant's *location*.
        $this->busy(dayOfWeek: 0, hour: 12, percentage: 0);
        $this->busy(dayOfWeek: 0, hour: 18, percentage: 100);
        $this->busy(dayOfWeek: 1, hour: 12, percentage: 50);

        $quiet = $this->restaurant->getAverageHours(['date' => '2027-06-06', 'start_time' => '12:00:00']);
        $slammed = $this->restaurant->getAverageHours(['date' => '2027-06-06', 'start_time' => '18:30:00']);
        $typical = $this->restaurant->getAverageHours(['date' => '2027-06-07', 'start_time' => '12:15:00']);

        $this->assertSame(1.0, $quiet);
        $this->assertSame(3.0, $slammed);
        $this->assertSame(2.0, $typical);
        $this->assertLessThan($slammed, $quiet);
    }

    public function test_most_recent_busyness_sample_wins(): void
    {
        $this->busy(dayOfWeek: 1, hour: 12, percentage: 100, collected: '2025-01-01');
        $this->busy(dayOfWeek: 1, hour: 12, percentage: 0, collected: '2026-01-01');
        $this->assertSame(1.0, $this->restaurant->getAverageHours(['date' => '2027-06-07', 'start_time' => '12:00:00']));
    }

    public function test_without_busyness_data_meals_take_the_default_time(): void
    {
        $this->assertSame(Restaurant::DEFAULT_AVERAGE_HOURS, $this->restaurant->getAverageHours());
        $this->assertSame(Restaurant::DEFAULT_AVERAGE_HOURS, $this->restaurant->getAverageHours(['date' => '2027-06-07', 'start_time' => '12:00:00']));
        $this->assertSame('14:00:00', $this->restaurant->calculateEndTime('2027-06-07', '12:00:00'));
    }

    public function test_area_comes_through_the_location(): void
    {
        $this->assertSame(Area::KANAB, $this->restaurant->area_id);
        $this->assertSame(Area::KANAB, $this->restaurant->area->id);
    }

    private function busy(int $dayOfWeek, int $hour, int $percentage, string $collected = '2026-06-01'): void
    {
        DB::table('resource_busy')->insert([
            'resource_table' => 'locations', 'resource_id' => $this->restaurant->location_id,
            'collected_date' => $collected, 'day_of_week' => $dayOfWeek, 'hour' => $hour, 'percentage' => $percentage,
        ]);
    }
}
