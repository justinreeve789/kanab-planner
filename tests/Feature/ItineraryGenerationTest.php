<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Itinerary\Period;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * End-to-end generation through GET /api/v1/itinerary against the fixture dataset. Generation is randomized, so
 * properties that must *always* hold are checked across several runs.
 */
class ItineraryGenerationTest extends TestCase
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

    public function test_total_days_matches_duration_of_trip(): void
    {
        $byDuration = $this->generateItinerary(['start_date' => '2027-06-01', 'duration_days' => 6]);
        $this->assertCount(6, $byDuration['days']);
        $this->assertSame('2027-06-06', $byDuration['end_date']);

        $byDates = $this->generateItinerary(['start_date' => '2027-06-01', 'end_date' => '2027-06-04', 'duration_days' => null]);
        $this->assertCount(4, $byDates['days']);
        $this->assertSame(4, $byDates['duration_days']);
    }

    public function test_days_are_in_order(): void
    {
        $itinerary = $this->generateItinerary(['start_date' => '2027-05-30', 'duration_days' => 7]);

        foreach ($itinerary['days'] as $index => $day) {
            $this->assertSame($index, $day['day_num']);
            $this->assertSame(date('Y-m-d', strtotime("2027-05-30 +{$index} days")), $day['date']);
        }
        $this->assertSame($itinerary['end_date'], end($itinerary['days'])['date']);
    }

    public function test_starts_and_ends_at_base_location_for_a_normal_arrival_and_departure(): void
    {
        $itinerary = $this->generateItinerary(['base_area_id' => Area::KANAB]);
        $first = $itinerary['days'][0];
        $last = end($itinerary['days']);

        $this->assertSame(Area::KANAB, $first['area']['id']);
        $this->assertSame(Area::KANAB, $last['area']['id']);
        $this->assertSame(['checking-in'], array_map(fn($r) => $this->typeOf($r['activity']), $this->activitiesOfDay($first)));
        $this->assertSame(['checking-out'], array_map(fn($r) => $this->typeOf($r['activity']), $this->activitiesOfDay($last)));
    }

    public function test_arriving_early_and_leaving_late_uses_the_first_and_last_days(): void
    {
        $itinerary = $this->generateItinerary([
            'start_date' => '2027-06-01',
            'end_date' => '2027-06-04',
            'arrival_datetime' => '2027-06-01 08:00:00',
            'departure_datetime' => '2027-06-04 21:00:00',
        ]);

        $this->assertCount(4, $itinerary['days']);
        $this->assertCount(4, $this->fullDays($itinerary), 'No day should be reduced to just checking in or out.');
    }

    public function test_merged_and_unusable_trails_are_never_scheduled(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary([
                'duration_days' => 8,
                'adventurousness' => 0.9,
                'trails' => implode(',', [$this->unusableTrailId, $this->mergedAwayTrailId]),
            ]);
            $trailIds = $this->resourceIdsOfTable($itinerary, 'trails');
            $this->assertNotContains($this->unusableTrailId, $trailIds);
            $this->assertNotContains($this->mergedAwayTrailId, $trailIds);
            $this->assertEmpty(array_diff($trailIds, $this->usableTrailIds()));
        }
    }

    public function test_no_trail_or_restaurant_is_scheduled_twice(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 9, 'adventurousness' => 0.9]);
            foreach (['trails', 'restaurants'] as $table) {
                $ids = $this->resourceIdsOfTable($itinerary, $table);
                $this->assertSame(array_values(array_unique($ids)), $ids, "A {$table} entry repeats.");
            }
        }
    }

    public function test_low_adventurousness_stays_within_an_hour_of_base(): void
    {
        foreach ([0.0, 0.2] as $adventurousness) {
            for ($run = 0; $run < self::RUNS; $run++) {
                $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => $adventurousness]);
                $this->assertEquals($adventurousness, $itinerary['adventurousness']);
                foreach ($itinerary['days'] as $day) {
                    $miles = $this->milesBetweenAreas(Area::KANAB, $day['area']['id']);
                    $this->assertLessThanOrEqual(60.5, $miles, "{$day['area']['name']} is {$miles} mi from Kanab (adventurousness {$adventurousness}).");
                }
            }
        }
    }

    public function test_moderate_adventurousness_visits_outlying_areas_at_most_once(): void
    {
        $bryceTrail = $this->fixtureTrailIds[Area::BRYCE][2];
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary([
                'duration_days' => 10,
                'adventurousness' => 0.5,
                'trails' => (string) $bryceTrail,
            ]);
            $counts = array_count_values(array_map(fn($d) => $d['area']['id'], $itinerary['days']));
            foreach ($counts as $areaId => $count) {
                if (!in_array($areaId, [Area::KANAB, Area::ZION], true)) {
                    $this->assertSame(1, $count, "Area {$areaId} was visited {$count} times.");
                }
            }
            $this->assertArrayHasKey(Area::BRYCE, $counts, 'The priority trail\'s area is part of the trip.');
        }
    }

    public function test_every_full_day_has_breakfast_lunch_and_dinner(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 5, 'adventurousness' => 0.5]);
            foreach ($this->fullDays($itinerary) as $day) {
                $meals = [];
                foreach ($this->activitiesOfDay($day) as $row) {
                    if (in_array($this->typeOf($row['activity']), ['cooking', 'dining'], true)) {
                        $meals[$row['period']] = true;
                    }
                }
                $this->assertSame(
                    [Period::MORNING, Period::AFTERNOON, Period::EVENING],
                    array_keys(array_filter([Period::MORNING => $meals[Period::MORNING] ?? false, Period::AFTERNOON => $meals[Period::AFTERNOON] ?? false, Period::EVENING => $meals[Period::EVENING] ?? false])),
                    "{$day['date']} in {$day['area']['name']} is missing a meal."
                );
            }
        }
    }

    public function test_home_cooked_lunch_only_happens_at_the_base_area(): void
    {
        foreach ([0.3, 0.6, 0.9] as $adventurousness) {
            $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => $adventurousness]);
            foreach ($this->activitiesOf($itinerary) as $row) {
                if ($row['period'] === Period::AFTERNOON && $this->typeOf($row['activity']) === 'cooking') {
                    $this->assertSame(Area::KANAB, $row['day']['area']['id'], "Lunch at home while in {$row['day']['area']['name']}.");
                }
            }
        }
    }

    public function test_relaxation_days_get_a_preamble_and_busy_days_do_not(): void
    {
        $sawRelaxationDay = false;
        for ($run = 0; $run < self::RUNS * 2 && !$sawRelaxationDay; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 7, 'adventurousness' => 0.1]);
            foreach ($this->fullDays($itinerary) as $day) {
                $hasTrail = in_array('trails', array_map(fn($r) => $r['activity']['resource_table'], $this->activitiesOfDay($day)), true);
                if ($hasTrail) {
                    $this->assertNull($day['preamble'], "{$day['date']} has a trail, so no relaxation blurb.");
                } elseif ($day['area']['id'] === Area::KANAB) {
                    $this->assertNotEmpty($day['preamble'], "{$day['date']} is a free day at base; it should say so.");
                    $sawRelaxationDay = true;
                }
            }
        }
        $this->assertTrue($sawRelaxationDay, 'Expected at least one free day at base for a low-adventurousness week.');
    }

    public function test_day_stats_reflect_what_was_scheduled(): void
    {
        $itinerary = $this->generateItinerary(['duration_days' => 6, 'adventurousness' => 0.9]);
        $sawTrailDay = false;
        foreach ($itinerary['days'] as $day) {
            $this->assertSame(['adventurousness', 'solitariness', 'strenuousness'], array_keys($day['stats']));
            foreach ($day['stats'] as $value) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(1, $value);
            }
            $hasTrail = in_array('trails', array_map(fn($r) => $r['activity']['resource_table'], $this->activitiesOfDay($day)), true);
            if ($hasTrail) {
                $sawTrailDay = true;
                $this->assertGreaterThan(0, $day['stats']['strenuousness'], "{$day['date']} has a hike but zero strenuousness.");
            }
        }
        $this->assertTrue($sawTrailDay);
    }

    public function test_priority_zion_trail_starts_early_with_breakfast_at_home(): void
    {
        $zionTrail = $this->fixtureTrailIds[Area::ZION][3]; // 4 hours: long enough to beat the crowds.
        $itinerary = $this->generateItinerary(['duration_days' => 5, 'trails' => (string) $zionTrail]);

        $hike = null;
        foreach ($this->activitiesOf($itinerary) as $row) {
            if (($row['activity']['resource_table'] ?? null) === 'trails' && $row['activity']['resource_id'] === $zionTrail) {
                $hike = $row;
            }
        }
        $this->assertNotNull($hike, 'The requested trail is in the itinerary.');
        $this->assertSame(Area::ZION, $hike['day']['area']['id']);
        $this->assertSame(Period::MORNING, $hike['period']);
        $this->assertSame('08:00:00', $hike['activity']['start_time']);
        $this->assertSame('12:00:00', $hike['activity']['end_time']);

        $breakfast = array_filter($this->activitiesOfDay($hike['day']), fn($r) => $r['activity']['name'] === 'Breakfast');
        $this->assertCount(1, $breakfast);
        $this->assertSame('06:30:00', array_values($breakfast)[0]['activity']['start_time']);
    }

    public function test_season_is_derived_from_the_start_date(): void
    {
        $this->assertSame('summer', $this->generateItinerary(['start_date' => '2027-07-04', 'season' => 'winter'])['season']);
        $this->assertSame('spring', $this->generateItinerary(['start_date' => '2027-04-10'])['season']);
    }

    public function test_activity_times_are_times_of_day(): void
    {
        $itinerary = $this->generateItinerary(['duration_days' => 6, 'adventurousness' => 0.8]);
        foreach ($this->activitiesOf($itinerary) as $row) {
            $this->assertMatchesRegularExpression('/^\d\d:\d\d:\d\d$/', (string) $row['activity']['start_time']);
            $this->assertMatchesRegularExpression('/^\d\d:\d\d:\d\d$/', (string) $row['activity']['end_time']);
        }
    }

    public function test_saved_itinerary_matches_loaded_itinerary(): void
    {
        $created = $this->generateItinerary(['duration_days' => 6, 'adventurousness' => 0.7]);
        $loaded = $this->getJson('/api/v1/itinerary/' . $created['id'])->assertOk()->json();

        $this->assertSame($this->shape($created), $this->shape($loaded));
    }

    /**
     * The parts of an itinerary that must survive a save/load round trip.
     */
    private function shape(array $itinerary): array
    {
        $days = $itinerary['days'];
        usort($days, fn($a, $b) => $a['day_num'] <=> $b['day_num']);
        return array_map(function($day) {
            $periods = [];
            foreach ($day['periods'] as $period) {
                $activities = array_map(fn($a) => [
                    $a['resource_table'] ?? null,
                    $a['resource_id'] ?? null,
                    $a['activity_type_id'],
                    $a['name'] ?? null,
                    $a['start_time'],
                    $a['end_time'],
                ], $period['activities']);
                sort($activities);
                $periods[(int) $period['type']] = $activities;
            }
            ksort($periods);
            return [$day['day_num'], $day['date'], $day['area']['id'] ?? $day['area_id'], $periods];
        }, $days);
    }
}
