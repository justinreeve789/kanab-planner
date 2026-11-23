<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\Area;
use App\Services\ItineraryService;
use Illuminate\Support\Facades\DB;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Staying somewhere other than Kanab: the base area, an optional pin and name for the lodging, check-in times, and
 * seasonal closures of the base itself.
 */
class HomeBaseTest extends TestCase
{
    use ItineraryAssertions;
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    public function test_a_trip_can_be_based_in_zion_at_a_named_and_pinned_lodging(): void
    {
        $itinerary = $this->generateItinerary([
            'base_area_id' => Area::ZION, 'lodging_name' => 'Zion Canyon Lodge', 'lat' => 37.2, 'lng' => -112.99,
            'start_date' => '2027-06-01', 'duration_days' => 4,
        ]);

        $this->assertSame(Area::ZION, $itinerary['base_area_id']);
        $this->assertSame('Zion', $itinerary['base_area']['short_name']);
        $this->assertSame('Zion Canyon Lodge', $itinerary['lodging_name']);
        $this->assertEqualsWithDelta(37.2, (float) $itinerary['latitude'], 0.0001);

        $first = $itinerary['days'][0];
        $last = end($itinerary['days']);
        $this->assertSame(Area::ZION, $first['area']['id']);
        $this->assertSame(Area::ZION, $last['area']['id']);

        $checkIn = $this->onlyActivityOfType($first, 'checking-in');
        $this->assertSame('Check in at Zion Canyon Lodge', $checkIn['name']);
        $this->assertNull($checkIn['resource_id'], 'Their own lodging is a note, not a listed accommodation.');
        $this->assertSame('16:00:00', $checkIn['start_time']);
        $this->assertSame('Check out of Zion Canyon Lodge', $this->onlyActivityOfType($last, 'checking-out')['name']);

        foreach ($itinerary['days'] as $day) {
            if ($day['area']['id'] === Area::ZION) {
                $this->assertSame(0, (int) $day['drive_minutes'], 'Days at the base need no drive.');
            }
        }
    }

    public function test_without_a_name_or_pin_a_listed_accommodation_in_the_base_area_is_used(): void
    {
        $kanabLocation = DB::table('locations')->where('area_id', Area::KANAB)->value('id');
        DB::table('accommodations')->where('id', 1)->update(['location_id' => $kanabLocation, 'check_in' => '15:00', 'check_out' => '11:00']);
        $this->assertSame(Area::KANAB, Accommodation::find(1)->area_id);

        $itinerary = $this->generateItinerary(['start_date' => '2027-06-01', 'duration_days' => 3]);
        $checkIn = $this->onlyActivityOfType($itinerary['days'][0], 'checking-in');
        $this->assertSame(1, $checkIn['resource_id']);
        $this->assertSame('15:00:00', $checkIn['start_time']);
        $this->assertSame('11:00:00', $this->onlyActivityOfType($itinerary['days'][2], 'checking-out')['start_time']);

        // The listing is in Kanab, so a Zion stay doesn't use it.
        $zion = $this->generateItinerary(['base_area_id' => Area::ZION, 'start_date' => '2027-06-01', 'duration_days' => 3]);
        $this->assertSame('Check in at your lodging', $this->onlyActivityOfType($zion['days'][0], 'checking-in')['name']);
    }

    public function test_check_in_and_out_times_can_be_given(): void
    {
        $itinerary = $this->generateItinerary([
            'base_area_id' => Area::ST_GEORGE, 'check_in' => '17:30', 'check_out' => '09:00',
            'start_date' => '2027-06-01', 'duration_days' => 3,
        ]);

        $this->assertSame('17:30:00', $this->onlyActivityOfType($itinerary['days'][0], 'checking-in')['start_time']);
        $this->assertSame('09:00:00', $this->onlyActivityOfType($itinerary['days'][2], 'checking-out')['start_time']);
    }

    public function test_a_pin_far_from_the_base_area_is_rejected(): void
    {
        $this->postJson('/api/v1/itinerary', [
            'base_area_id' => Area::KANAB, 'lat' => Area::find(Area::ST_GEORGE)->latitude, 'lng' => Area::find(Area::ST_GEORGE)->longitude,
            'start_date' => '2027-06-01', 'duration_days' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('lat');

        $this->postJson('/api/v1/itinerary', ['lat' => 37.0, 'start_date' => '2027-06-01'])
            ->assertStatus(422)->assertJsonValidationErrors('lng');
    }

    public function test_a_base_that_is_closed_on_the_chosen_dates_is_rejected(): void
    {
        $this->postJson('/api/v1/itinerary', [
            'base_area_id' => Area::NORTH_RIM, 'start_date' => '2027-01-10', 'duration_days' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('base_area_id')
            ->assertJsonPath('errors.base_area_id.0', 'Grand Canyon (North Rim) is closed from December 1 to May 14. Choose somewhere else to stay, or different dates.');
    }

    public function test_flexible_dates_move_to_a_month_when_the_base_is_open(): void
    {
        for ($run = 0; $run < 3; $run++) {
            $itinerary = $this->generateItinerary([
                'base_area_id' => Area::NORTH_RIM, 'start_date' => null, 'preferred_months' => [1, 2, 7], 'duration_days' => 3,
            ]);
            $this->assertSame('07', substr($itinerary['start_date'], 5, 2));
        }

        $this->postJson('/api/v1/itinerary', ['base_area_id' => Area::NORTH_RIM, 'preferred_months' => [1, 2], 'duration_days' => 3])
            ->assertStatus(422)->assertJsonValidationErrors('base_area_id');
    }

    public function test_areas_near_the_base_can_come_up_more_than_once(): void
    {
        $service = new ItineraryService();
        $core = fn(int $baseId) => (fn() => $this->coreAreaIds(Area::find($baseId)))->call($service);

        $this->assertEqualsCanonicalizing([Area::KANAB, Area::ZION], $core(Area::KANAB));
        $this->assertContains(Area::KANAB, $core(Area::ZION));
        $this->assertEqualsCanonicalizing([Area::BRYCE], $core(Area::BRYCE));
        $this->assertEqualsCanonicalizing([Area::PAGE_AZ, Area::LAKE_POWELL], $core(Area::PAGE_AZ));
    }

    private function onlyActivityOfType(array $day, string $type): array
    {
        $matches = array_values(array_filter($this->activitiesOfDay($day), fn($row) => $this->typeOf($row['activity']) === $type));
        $this->assertCount(1, $matches, "Expected one {$type} on day {$day['day_num']}.");
        return $matches[0]['activity'];
    }
}
