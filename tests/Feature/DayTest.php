<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Period;
use App\Models\Restaurant;
use App\Models\Trail;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class DayTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    public function test_building_a_day_from_an_area_sets_area_id(): void
    {
        $day = new Day(['date' => '2027-06-01', 'area' => Area::find(Area::ZION), 'day_num' => 1]);
        $this->assertSame(Area::ZION, $day->area_id);
        $this->assertSame(Area::ZION, $day->area->id);
        $this->assertCount(4, $day->getPeriods(), 'Every day gets morning, afternoon, evening and night.');
    }

    public function test_formatted_dates(): void
    {
        $day = new Day(['date' => '2027-07-04', 'area' => Area::find(Area::KANAB), 'day_num' => 0]);
        $json = $day->toArray();
        $this->assertSame('Jul 4', $json['formatted_abbr_month_day']);
        $this->assertSame('Sunday, July 4, 2027', $json['formatted_date']);
        $this->assertSame('Sunday', $json['formatted_day_of_week']);
        $this->assertSame('2027', $json['formatted_year']);
    }

    public function test_has_trail_and_restaurant_reflect_scheduled_resources(): void
    {
        $day = new Day(['date' => '2027-06-01', 'area' => Area::find(Area::KANAB), 'day_num' => 1]);
        $this->assertFalse($day->hasTrail());

        $trail = Trail::find($this->fixtureTrailIds[Area::KANAB][1]);
        $day->getPeriod(Period::MORNING)->addActivity(new Activity(['resource' => $trail, 'start_time' => '09:00:00']));
        $restaurant = Restaurant::find($this->fixtureRestaurantIds[Area::KANAB][0]);
        $day->getPeriod(Period::EVENING)->addActivity(new Activity(['resource' => $restaurant, 'start_time' => '17:00:00']));

        $this->assertTrue($day->hasTrail());
        $this->assertTrue($day->hasRestaurant());
        $this->assertFalse($day->hasEvent());
        $this->assertTrue($day->getPeriod(Period::EVENING)->hasRestaurant());
        $this->assertFalse($day->getPeriod(Period::MORNING)->hasRestaurant());
    }

    public function test_preamble_is_serialized(): void
    {
        $day = new Day(['date' => '2027-06-01', 'area' => Area::find(Area::KANAB), 'day_num' => 1]);
        $this->assertNull($day->toArray()['preamble']);
        $day->setPreamble('Take it easy today.');
        $this->assertSame('Take it easy today.', $day->toArray()['preamble']);
    }

    public function test_weather_uses_the_real_daily_high_and_low(): void
    {
        foreach ([['06:00', 58, 'Clear'], ['12:00', 84, 'Sunny'], ['15:00', 91, 'Mostly Sunny'], ['21:00', 66, 'Clear']] as [$time, $temp, $forecast]) {
            DB::table('area_weather')->insert([
                'area_id' => Area::KANAB, 'source_type' => 'nws',
                'start' => "2027-06-01 {$time}:00", 'end' => "2027-06-01 {$time}:59",
                'temperature' => $temp, 'temperature_unit' => 'F',
                'short_forecast' => $forecast, 'detailed_forecast' => "{$forecast}, light wind.",
            ]);
        }

        $weather = (new Day(['date' => '2027-06-01', 'area' => Area::find(Area::KANAB), 'day_num' => 0]))->weather;

        $this->assertSame(91, $weather->temperatures->high);
        $this->assertSame(58, $weather->temperatures->low);
        $this->assertSame('Sunny', $weather->forecast->short, 'The first afternoon forecast describes the day.');
        $this->assertStringEndsWith('.svg', $weather->icon);
    }

    public function test_weather_without_forecast_rows(): void
    {
        $weather = (new Day(['date' => '2027-06-01', 'area' => Area::find(Area::KANAB), 'day_num' => 0]))->weather;
        $this->assertNull($weather->temperatures->high);
        $this->assertNull($weather->forecast->short);
        $this->assertNull($weather->icon);
    }
}
