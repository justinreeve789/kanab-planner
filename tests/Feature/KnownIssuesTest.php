<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Assistant\Message;
use App\Models\Itinerary\Period;
use App\Models\Trail;
use App\Services\ConversionService;
use App\Services\DataService;
use App\Services\TrailService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\GeoJson;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Executable specs for problems that are documented but deliberately NOT fixed in this pass, because fixing them
 * changes product behavior or needs a design decision. They fail today. Excluded from the default run; see them
 * with:
 *
 *   vendor/bin/phpunit --group known-issues
 *
 * When one is fixed, move it into the regular suite.
 */
#[Group('known-issues')]
class KnownIssuesTest extends TestCase
{
    use ItineraryAssertions;
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
    }

    /**
     * Activities are dropped into periods at fixed start times with no conflict check: a 12:00–16:00 hike and a
     * 12:00–14:00 lunch land in the same afternoon, and an 8-hour morning hike runs straight through lunch and
     * dinner.
     */
    public function test_activities_in_a_day_do_not_overlap(): void
    {
        for ($run = 0; $run < 4; $run++) {
            $itinerary = $this->generateItinerary(['duration_days' => 8, 'adventurousness' => 0.9, 'strenuousness' => 0.9]);
            foreach ($this->fullDays($itinerary) as $day) {
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

    /**
     * TrailService::getBestPeriodOfDayForTrail() never returns EVENING (its docblock says short, easy trails should
     * be evening hikes), and getRandomTrailForPeriod() only returns trails whose best period matches, so
     * addTrailForEvening() can never schedule anything.
     */
    public function test_short_easy_trails_can_be_evening_hikes(): void
    {
        $trail = new Trail(['area_id' => Area::KANAB, 'average_hours' => 0.5, 'difficulty' => 'Easy']);
        $this->assertSame(Period::EVENING, (new TrailService())->getBestPeriodOfDayForTrail($trail));
    }

    /**
     * The North Rim is closed December 1 – May 14 (TODO in ItineraryService::assignDaysForAreas).
     */
    public function test_north_rim_not_selected_during_offseason(): void
    {
        for ($run = 0; $run < 6; $run++) {
            $itinerary = $this->generateItinerary(['start_date' => '2027-01-10', 'duration_days' => 10, 'adventurousness' => 0.9]);
            $this->assertNotContains(Area::NORTH_RIM, array_map(fn($d) => $d['area']['id'], $itinerary['days']));
        }
    }

    /**
     * addMeal() picks lunch/dinner restaurants without checking opening hours (the whereIsOpen() call is commented
     * out); only the cooking fallback path checks them.
     */
    public function test_restaurants_not_selected_when_closed(): void
    {
        // Every Kanab restaurant is closed on Wednesdays (day_of_week 3).
        $locationIds = DB::table('restaurants')->whereIn('id', $this->fixtureRestaurantIds[Area::KANAB])->pluck('location_id');
        DB::table('location_hours')->whereIn('location_id', $locationIds)->where('day_of_week', 3)->delete();

        for ($run = 0; $run < 4; $run++) {
            $itinerary = $this->generateItinerary(['start_date' => '2027-06-01', 'duration_days' => 8, 'adventurousness' => 0.0]);
            foreach ($this->activitiesOf($itinerary) as $row) {
                if (($row['activity']['resource_table'] ?? null) === 'restaurants'
                    && in_array($row['activity']['resource_id'], $this->fixtureRestaurantIds[Area::KANAB], true)) {
                    $this->assertNotSame('3', date('w', strtotime($row['day']['date'])), "Closed restaurant scheduled on {$row['day']['date']}");
                }
            }
        }
    }

    /**
     * Away-day activities start at fixed times (09:00) regardless of how far the area is from base, e.g. staying at
     * the North Rim and hiking at Bryce at 09:00 means leaving before 06:30.
     */
    public function test_activity_start_time_accounts_for_long_travel(): void
    {
        DB::table('area_distances')->insert(['from_area_id' => Area::NORTH_RIM, 'to_area_id' => Area::BRYCE, 'driving_hours' => 2.6]);
        for ($run = 0; $run < 6; $run++) {
            $itinerary = $this->generateItinerary(['base_area_id' => Area::NORTH_RIM, 'duration_days' => 8, 'adventurousness' => 0.9]);
            foreach ($this->fullDays($itinerary) as $day) {
                if ($day['area']['id'] !== Area::BRYCE) {
                    continue;
                }
                foreach ($this->activitiesOfDay($day) as $row) {
                    if ($row['activity']['resource_table'] === 'trails') {
                        $this->assertGreaterThanOrEqual('09:36:00', $row['activity']['start_time'], 'Leave at 07:00 + 2.6h drive.');
                    }
                }
            }
        }
    }

    /**
     * CustomQueryBuilder::betweenDates() matches events that *contain* the whole window, so a 09:00–12:00 event is
     * never offered for the 06:00–11:00 morning slot. An overlap test is probably what's wanted.
     */
    public function test_events_overlapping_a_period_are_offered(): void
    {
        $locationId = DB::table('locations')->insertGetId(['name' => 'Kanab Park', 'area_id' => Area::KANAB]);
        $eventId = DB::table('events')->insertGetId([
            'name' => 'Farmers Market', 'source_type' => 'fixture', 'source_id' => 'market', 'location_id' => $locationId,
            'start_date' => '2027-06-02 09:00:00', 'end_date' => '2027-06-02 12:00:00',
        ]);
        DB::table('event_dates')->insert(['event_id' => $eventId, 'start_date' => '2027-06-02 09:00:00', 'end_date' => '2027-06-02 12:00:00']);

        $this->assertSame(1, \App\Models\Event::betweenDates('2027-06-02 06:00:00', '2027-06-02 11:00:00')->count());
    }

    /**
     * DataService::calculateStrenuousnessForTrail() feeds raw foot/percent differences into steep sigmoids, so the
     * terms saturate to 0 or 1: a trail that climbs more than it descends gets a base score of 0, and a long steep
     * climb can score *below* a short flat walk.
     */
    public function test_longer_steeper_trails_are_more_strenuous(): void
    {
        $hiking = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $data = new DataService();
        $stroll = new Trail(['distance_miles' => 1.0, 'incline_ft' => 20, 'decline_ft' => 20, 'average_grade_pt' => 0.01, 'max_grade_pt' => 0.03, 'difficulty' => 'Intermediate', 'average_hours' => 0.5]);
        $climb = new Trail(['distance_miles' => 9.0, 'incline_ft' => 2800, 'decline_ft' => 300, 'average_grade_pt' => 0.12, 'max_grade_pt' => 0.35, 'difficulty' => 'Intermediate', 'average_hours' => 0.5]);

        $this->assertGreaterThan(
            $data->calculateStrenuousnessForTrail($stroll, $hiking),
            $data->calculateStrenuousnessForTrail($climb, $hiking)
        );
    }

    /**
     * TrailService::calculateTime() applies "distance fatigue" (log(0.01 + dist) / 7) per GPS segment, not per
     * trail, so the same path recorded with denser points is estimated as much slower.
     */
    public function test_hiking_estimate_does_not_depend_on_gps_point_density(): void
    {
        $service = new TrailService();
        // The same 3.5-mile climb, sampled every ~0.35 mi vs every ~18 ft.
        $sparse = $service->analyzeTrail(GeoJson::line(37.0, -112.5, 37.05, -112.5, 10, 1500, 1800), 2.0, ActivityType::HIKING, 2);
        $dense = $service->analyzeTrail(GeoJson::line(37.0, -112.5, 37.05, -112.5, 1000, 1500, 1800), 2.0, ActivityType::HIKING, 2);

        $this->assertEqualsWithDelta($sparse['total_hours'], $dense['total_hours'], 0.10 * $sparse['total_hours']);
    }

    /**
     * Message::transformLinks() runs str_ireplace() for every known name, so a name that's part of another name
     * (or of a URL already inserted) gets wrapped again, producing nested/broken anchors.
     */
    public function test_transform_links_does_not_nest_anchors(): void
    {
        DB::table('locations')->insert(['name' => 'Zion National Park', 'area_id' => Area::ZION, 'url' => 'https://www.nps.gov/zion/']);
        DB::table('locations')->insert(['name' => 'Zion', 'area_id' => Area::ZION, 'url' => 'https://example.test/zion']);

        $html = Message::transformLinks('Visit Zion National Park early.');
        $this->assertSame(1, substr_count($html, '<a '), $html);
    }

    /**
     * ConversionService::extractImagesFromHtml() uses greedy .+ / .* so it finds at most one image per line, and
     * background-image URLs land in an unnamed group (returned as empty strings).
     */
    public function test_extracts_every_image_on_a_line(): void
    {
        $html = '<img src="/a.jpg"><img src="/b.jpg"><div style="background-image: url(/c.png)"></div>';
        $this->assertSame(['/a.jpg', '/b.jpg', '/c.png'], (new ConversionService())->extractImagesFromHtml($html));
    }
}
