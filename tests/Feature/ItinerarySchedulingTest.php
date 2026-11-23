<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Timeline;
use Illuminate\Support\Facades\DB;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Time-of-day rules: no double-booking, drive time, opening hours, seasonal closures and the user's requests.
 * Generation is randomized, so "always" properties run several times.
 */
class ItinerarySchedulingTest extends TestCase
{
    use ItineraryAssertions;
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private const RUNS = 4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    public function test_activities_in_a_day_do_not_overlap(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => 0.9, 'strenuousness' => 0.9]);
            foreach ($itinerary['days'] as $day) {
                $slots = [];
                foreach ($this->activitiesOfDay($day) as $row) {
                    $a = $row['activity'];
                    if ($a['start_time'] !== $a['end_time']) {
                        $slots[] = [$a['start_time'], $a['end_time'], $a['resource']['name'] ?? $a['name']];
                    }
                }
                usort($slots, fn($x, $y) => strcmp($x[0], $y[0]));
                for ($i = 1; $i < count($slots); $i++) {
                    $this->assertLessThanOrEqual(
                        $slots[$i][0], $slots[$i - 1][1],
                        "{$day['date']}: {$slots[$i - 1][2]} ({$slots[$i - 1][0]}–{$slots[$i - 1][1]}) overlaps {$slots[$i][2]} ({$slots[$i][0]}–{$slots[$i][1]})"
                    );
                }
            }
        }
    }

    public function test_period_matches_the_clock(): void
    {
        $itinerary = $this->generateItinerary(['duration_days' => 6, 'adventurousness' => 0.8]);
        foreach ($this->activitiesOf($itinerary) as $row) {
            $this->assertSame(Period::forTime($row['activity']['start_time']), $row['period'], json_encode($row['activity']));
        }
    }

    public function test_away_days_leave_time_to_drive_there_and_back(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => 0.9]);
            foreach ($this->fullDays($itinerary) as $day) {
                $drive = $day['drive_minutes'];
                if ($day['area']['id'] === Area::KANAB) {
                    $this->assertSame(0, $drive);
                    continue;
                }
                $this->assertGreaterThan(0, $drive);
                foreach ($this->activitiesOfDay($day) as $row) {
                    $a = $row['activity'];
                    if ($a['resource_table'] === null) {
                        continue; // Home meals and notes.
                    }
                    $this->assertGreaterThanOrEqual(Timeline::addMinutes('07:00', $drive), $a['start_time'],
                        "{$a['resource']['name']} on {$day['date']} starts before you could drive {$drive} min to {$day['area']['name']}.");
                    $this->assertLessThanOrEqual('21:00:00', $a['end_time']);
                }
            }
        }
    }

    public function test_activity_start_time_accounts_for_long_travel(): void
    {
        DB::table('area_distances')->insert(['from_area_id' => Area::NORTH_RIM, 'to_area_id' => Area::BRYCE, 'driving_hours' => 2.6, 'driving_miles' => 130]);
        $sawBryce = false;
        for ($run = 0; $run < 6; $run++) {
            $itinerary = $this->generateItinerary(['base_area_id' => Area::NORTH_RIM, 'duration_days' => 8, 'adventurousness' => 0.9]);
            foreach ($this->fullDays($itinerary) as $day) {
                if ($day['area']['id'] !== Area::BRYCE) {
                    continue;
                }
                $sawBryce = true;
                $this->assertSame(156, $day['drive_minutes']);
                foreach ($this->activitiesOfDay($day) as $row) {
                    if ($row['activity']['resource_table'] === 'trails') {
                        $this->assertGreaterThanOrEqual('09:36:00', $row['activity']['start_time'], 'Leave at 07:00 + 2.6h drive.');
                    }
                }
            }
        }
        $this->assertTrue($sawBryce, 'Expected at least one Bryce day from a North Rim base.');
    }

    public function test_long_hikes_over_lunch_get_a_packed_lunch_note(): void
    {
        $longTrail = $this->fixtureTrailIds[Area::ZION][4]; // 8 hours, starts in the morning.
        $itinerary = $this->generateItinerary(['duration_days' => 4, 'adventurousness' => 0.9, 'strenuousness' => 0.9, 'trail_ids' => [$longTrail]]);
        foreach ($itinerary['days'] as $day) {
            $rows = $this->activitiesOfDay($day);
            $hike = collect($rows)->first(fn($r) => $r['activity']['resource_id'] === $longTrail && $r['activity']['resource_table'] === 'trails');
            if (!$hike) {
                continue;
            }
            $names = array_map(fn($r) => $r['activity']['name'], $rows);
            $this->assertContains('Pack a lunch', $names);
            $dinner = collect($rows)->first(fn($r) => $r['period'] === Period::EVENING && in_array($this->typeOf($r['activity']), ['dining', 'cooking'], true));
            $this->assertNotNull($dinner, 'Dinner still happens after a long hike.');
            $this->assertGreaterThanOrEqual($hike['activity']['end_time'], $dinner['activity']['start_time']);
            return;
        }
        $this->fail('The requested 8-hour trail was not scheduled.');
    }

    public function test_short_easy_trails_become_evening_hikes(): void
    {
        $sawEvening = false;
        for ($run = 0; $run < self::RUNS * 2 && !$sawEvening; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => 0.9, 'strenuousness' => 0.2]);
            foreach ($this->activitiesOf($itinerary) as $row) {
                if ($row['activity']['resource_table'] === 'trails' && $row['period'] === Period::EVENING) {
                    $sawEvening = true;
                    $this->assertLessThanOrEqual(2.0, (float) $row['activity']['resource']['distance_miles']);
                }
            }
        }
        $this->assertTrue($sawEvening, 'Expected an evening hike in a week of easy, adventurous days.');
    }

    public function test_north_rim_not_selected_during_offseason(): void
    {
        for ($run = 0; $run < 6; $run++) {
            $itinerary = $this->generateItinerary(['start_date' => '2027-01-10', 'duration_days' => 10, 'adventurousness' => 0.9]);
            $this->assertNotContains(Area::NORTH_RIM, array_map(fn($d) => $d['area']['id'], $itinerary['days']));
        }
    }

    public function test_requested_north_rim_trail_is_skipped_while_it_is_closed(): void
    {
        $northRimTrail = $this->fixtureTrailIds[Area::NORTH_RIM][2];
        $winter = $this->generateItinerary(['start_date' => '2027-02-01', 'duration_days' => 4, 'trail_ids' => [$northRimTrail]]);
        $this->assertNotContains(Area::NORTH_RIM, array_map(fn($d) => $d['area']['id'], $winter['days']));

        $summer = $this->generateItinerary(['start_date' => '2027-07-01', 'duration_days' => 4, 'trail_ids' => [$northRimTrail]]);
        $this->assertContains($northRimTrail, $this->resourceIdsOfTable($summer, 'trails'));
    }

    public function test_restaurants_not_selected_when_closed(): void
    {
        // Every Kanab restaurant is closed on Wednesdays (day_of_week 3); 2027-06-02 is a Wednesday.
        $locationIds = DB::table('restaurants')->whereIn('id', $this->fixtureRestaurantIds[Area::KANAB])->pluck('location_id');
        DB::table('location_hours')->whereIn('location_id', $locationIds)->where('day_of_week', 3)->delete();

        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary(['start_date' => '2027-06-01', 'duration_days' => 8, 'adventurousness' => 0.0]);
            foreach ($this->activitiesOf($itinerary) as $row) {
                if (($row['activity']['resource_table'] ?? null) === 'restaurants'
                    && in_array($row['activity']['resource_id'], $this->fixtureRestaurantIds[Area::KANAB], true)) {
                    $this->assertNotSame('3', date('w', strtotime($row['day']['date'])), "Closed restaurant scheduled on {$row['day']['date']}");
                }
            }
        }
    }

    public function test_restaurant_without_any_hours_counts_as_open_in_the_daytime(): void
    {
        $locationIds = DB::table('restaurants')->whereIn('id', $this->fixtureRestaurantIds[Area::KANAB])->pluck('location_id');
        DB::table('location_hours')->whereIn('location_id', $locationIds)->delete();

        $itinerary = $this->generateItinerary(['duration_days' => 4, 'adventurousness' => 0.0]);
        $kanabMeals = array_filter($this->activitiesOf($itinerary), fn($r) => $r['activity']['resource_table'] === 'restaurants');
        $this->assertNotEmpty($kanabMeals);
        foreach ($kanabMeals as $row) {
            $this->assertGreaterThanOrEqual('08:00:00', $row['activity']['start_time']);
            $this->assertLessThan('20:00:00', $row['activity']['start_time']);
        }
    }

    public function test_requested_event_is_scheduled_on_its_date_in_its_area(): void
    {
        $locationId = DB::table('locations')->insertGetId(['name' => 'Bryce Canyon Lodge', 'area_id' => Area::BRYCE]);
        $eventId = DB::table('events')->insertGetId([
            'name' => 'Bryce Canyon Astronomy Festival', 'source_type' => 'fixture', 'source_id' => 'astro', 'location_id' => $locationId,
            'start_date' => '2027-06-04 20:00:00', 'end_date' => '2027-06-04 21:30:00',
        ]);
        DB::table('event_dates')->insert(['event_id' => $eventId, 'start_date' => '2027-06-04 20:00:00', 'end_date' => '2027-06-04 21:30:00']);

        $itinerary = $this->generateItinerary(['start_date' => '2027-06-01', 'duration_days' => 6, 'adventurousness' => 0.2, 'event_ids' => [$eventId]]);
        $rows = array_values(array_filter($this->activitiesOf($itinerary), fn($r) => $r['activity']['resource_table'] === 'events'));

        $this->assertCount(1, $rows);
        $this->assertSame('2027-06-04', $rows[0]['day']['date']);
        $this->assertSame(Area::BRYCE, $rows[0]['day']['area']['id']);
        $this->assertSame('20:00:00', $rows[0]['activity']['start_time']);
    }

    public function test_requested_places_restaurants_and_areas_are_included(): void
    {
        $restaurant = $this->fixtureRestaurantIds[Area::PAGE_AZ][4];
        $location = DB::table('locations')->insertGetId(['name' => 'Horseshoe Bend Overlook', 'area_id' => Area::PAGE_AZ, 'is_usable' => 1]);

        $itinerary = $this->generateItinerary([
            'duration_days' => 6, 'adventurousness' => 0.2,
            'restaurant_ids' => [$restaurant], 'location_ids' => [$location], 'area_ids' => [Area::ST_GEORGE],
        ]);

        $this->assertContains($restaurant, $this->resourceIdsOfTable($itinerary, 'restaurants'));
        $this->assertContains($location, $this->resourceIdsOfTable($itinerary, 'locations'));
        $this->assertContains(Area::ST_GEORGE, array_map(fn($d) => $d['area']['id'], $itinerary['days']));
        $sightseeing = collect($this->activitiesOf($itinerary))->first(fn($r) => $r['activity']['resource_table'] === 'locations');
        $this->assertSame('sightseeing', $this->typeOf($sightseeing['activity']));
    }

    public function test_no_trails_when_no_trail_activities_were_chosen(): void
    {
        $itinerary = $this->generateItinerary(['duration_days' => 6, 'adventurousness' => 0.9, 'preferred_activity_types' => ['museum', 'stargazing']]);
        $this->assertSame([], $this->resourceIdsOfTable($itinerary, 'trails'));
    }

    public function test_trail_days_follow_adventurousness(): void
    {
        $trailDays = function(float $adventurousness) {
            $itinerary = $this->generateItinerary(['duration_days' => 10, 'adventurousness' => $adventurousness]);
            return count(array_filter($itinerary['days'], fn($d) => in_array('trails', array_column(array_column($this->activitiesOfDay($d), 'activity'), 'resource_table'), true)));
        };
        $this->assertLessThanOrEqual(3, $trailDays(0.1), 'About a quarter of 8 full days.');
        $this->assertGreaterThanOrEqual(6, $trailDays(1.0), 'Nearly every full day.');
    }

    public function test_preferred_months_pick_the_dates_when_none_are_given(): void
    {
        $itinerary = $this->generateItinerary(['start_date' => null, 'duration_days' => 3, 'preferred_months' => [10]]);
        $this->assertSame('10', date('m', strtotime($itinerary['start_date'])));
    }

    public function test_total_driving_distance_is_reported(): void
    {
        $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => 0.9]);
        $awayDays = array_filter($itinerary['days'], fn($d) => $d['area']['id'] !== Area::KANAB);
        $this->assertSame($awayDays === [], $itinerary['total_travel_distance'] === 0);
    }
}
