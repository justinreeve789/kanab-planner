<?php

namespace Tests\Feature\Chat;

use App\Chat\ChatContext;
use App\Chat\Tools\ToolRegistry;
use App\Models\Area;
use App\Models\TrailGeoJson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeoJson;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class ChatToolsTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private ToolRegistry $tools;
    private ChatContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seedKanabFixtures();
        $this->tools = ToolRegistry::default();
        $this->context = new ChatContext(CarbonImmutable::parse('2026-07-10 09:00', 'America/Denver'));
    }

    private function tool(string $tool, array $args): array
    {
        return $this->tools->get($tool)->handle($args, $this->context)->data;
    }

    public function test_search_flags_ambiguous_names_and_folds_duplicates(): void
    {
        $a = $this->insertTrail(['name' => 'Canyon Overlook Trail', 'area_id' => Area::ZION, 'latitude' => 37.21, 'longitude' => -112.94]);
        $dup = $this->insertTrail(['name' => 'Canyon Overlook', 'area_id' => Area::ZION, 'latitude' => 37.211, 'longitude' => -112.941]);
        $b = $this->insertTrail(['name' => 'Timber Creek Overlook', 'area_id' => Area::ZION, 'latitude' => 37.45, 'longitude' => -113.19]);

        $result = $this->tool('search_trails', ['query' => 'Overlook']);
        $this->assertTrue($result['ambiguous']);
        $ids = array_column($result['matches'], 'trail_id');
        $this->assertContains($b, $ids);
        $this->assertCount(1, array_intersect([$a, $dup], $ids));
        $this->assertSame(1, $result['duplicates_folded']);

        $exact = $this->tool('search_trails', ['query' => 'timber creek overlook']);
        $this->assertFalse($exact['ambiguous']);
        $this->assertSame($b, $exact['matches'][0]['trail_id']);
    }

    public function test_search_tolerates_typos_and_ignores_numeric_aliases(): void
    {
        $id = $this->insertTrail(['name' => "Angel's Landing Trail", 'name_alt' => '7', 'area_id' => Area::ZION]);
        $this->assertSame($id, $this->tool('search_trails', ['query' => 'angles landing'])['matches'][0]['trail_id']);
        $this->assertSame([], $this->tool('search_trails', ['query' => '7'])['matches']);
    }

    public function test_search_filters_by_distance_and_area(): void
    {
        $result = $this->tool('search_trails', ['area_id' => Area::KANAB, 'max_miles' => 3]);
        $this->assertNotEmpty($result['matches']);
        foreach ($result['matches'] as $match) {
            $this->assertSame(Area::KANAB, $match['area_id']);
            $this->assertLessThanOrEqual(3, $match['distance_miles']);
        }
    }

    public function test_trail_profile_explains_a_missing_track(): void
    {
        $id = $this->fixtureTrailIds[Area::KANAB][0];
        $this->assertTrue($this->tool('trail_profile', ['trail_id' => $id])['unavailable']);

        TrailGeoJson::create(['trail_id' => $id, 'geojson' => GeoJson::line(37.0, -112.5, 37.0 + 1 / 69.05, -112.5, 100, 1500, 1500 + 160.9)]);
        $data = $this->tool('trail_profile', ['trail_id' => $id]);
        $this->assertEqualsWithDelta(10, $data['measured']['steepest_sustained']['grade_pct'], 1);
        $this->assertSame('round_trip', $data['as_hiked']['plan']);
        $this->assertEqualsWithDelta(2.0, $data['as_hiked']['miles'], 0.1);
    }

    public function test_trail_knowledge_finds_linked_and_mentioning_blog_posts(): void
    {
        $trail = $this->insertTrail(['name' => 'Wire Pass Trail', 'area_id' => Area::KANAB]);
        $blog = DB::table('blogs')->insertGetId(['name' => 'Desert Blog', 'url' => 'https://blog.test']);
        DB::table('blog_posts')->insert([
            ['blog_id' => $blog, 'post_id' => 1, 'title' => 'Wire Pass guide', 'resource_table' => 'trails', 'resource_id' => $trail, 'posted_at' => '2025-05-01',
                'content' => '<p>Bring water shoes and a headlamp for the slot.</p><p>We loved it.</p>', 'raw_data' => json_encode(['link' => 'https://blog.test/wire-pass'])],
            ['blog_id' => $blog, 'post_id' => 2, 'title' => 'Kanab roundup', 'resource_table' => null, 'resource_id' => null, 'posted_at' => '2024-03-01',
                'content' => '<p>Near Kanab, the Wire Pass trailhead is down a dirt road. For Wire Pass you should pack plenty of water.</p>', 'raw_data' => null],
            ['blog_id' => $blog, 'post_id' => 3, 'title' => 'Ignore me', 'resource_table' => null, 'resource_id' => null, 'posted_at' => '2024-03-01',
                'content' => '<p>Wire Pass in Colorado: pack a parka.</p>', 'raw_data' => null],
        ]);

        $data = $this->tool('trail_knowledge', ['trail_id' => $trail, 'topic' => 'gear']);
        $texts = array_column($data['blog_excerpts'], 'text');
        $this->assertContains('Bring water shoes and a headlamp for the slot.', $texts);
        $this->assertContains('For Wire Pass you should pack plenty of water.', $texts);
        $this->assertNotContains('Wire Pass in Colorado: pack a parka.', $texts, 'Posts that never name the area are skipped.');
        $this->assertSame('https://blog.test/wire-pass', $data['blog_excerpts'][0]['url']);
        $this->assertTrue($data['blog_excerpts'][0]['linked_to_record']);
    }

    public function test_find_restaurants_open_late_with_word_evidence(): void
    {
        $location = DB::table('locations')->insertGetId(['name' => 'Green Leaf', 'area_id' => Area::KANAB, 'latitude' => 37.05, 'longitude' => -112.53, 'is_usable' => 1]);
        DB::table('location_hours')->insert(['location_id' => $location, 'day_of_week' => 0, 'time_open' => '17:00:00', 'time_close' => '23:00:00']);
        $id = DB::table('restaurants')->insertGetId(['name' => 'Green Leaf', 'source_type' => 't', 'source_id' => 'g', 'location_id' => $location,
            'description' => 'Plant-based bowls; everything can be made vegan.', 'is_usable' => 1]);

        $late = $this->tool('find_restaurants', ['area_id' => Area::KANAB, 'open_at' => '2026-07-12 22:30']);
        $this->assertSame([$id], array_column($late['restaurants'], 'restaurant_id'), 'Fixture diners close at 22:00.');

        $vegan = $this->tool('find_restaurants', ['area_id' => Area::KANAB, 'query' => 'vegan options']);
        $this->assertSame($id, $vegan['restaurants'][0]['restaurant_id']);
        $this->assertStringContainsString('vegan', $vegan['restaurants'][0]['matches_because'][0]['text']);
    }

    public function test_forecast_summarizes_the_window_and_implies_gear(): void
    {
        Http::fake([
            'api.weather.gov/points/*' => Http::response(['properties' => ['forecastHourly' => 'https://api.weather.gov/gridpoints/SLC/57,12/forecast/hourly', 'forecast' => 'https://api.weather.gov/gridpoints/SLC/57,12/forecast']]),
            'api.weather.gov/gridpoints/SLC/57,12/forecast/hourly' => Http::response(['properties' => ['periods' => array_map(fn($h) => [
                'startTime' => sprintf('2026-07-10T%02d:00:00-06:00', $h), 'temperature' => 70 + $h, 'windSpeed' => '10 mph', 'windDirection' => 'SW',
                'probabilityOfPrecipitation' => ['value' => $h === 15 ? 60 : 10], 'shortForecast' => $h === 15 ? 'Thunderstorms' : 'Sunny',
            ], range(6, 20))]]),
            'api.weather.gov/gridpoints/SLC/57,12/forecast' => Http::response(['properties' => ['periods' => [
                ['name' => 'Today', 'startTime' => '2026-07-10T06:00:00-06:00', 'detailedForecast' => 'Sunny, then storms after 2pm.'],
            ]]]),
            'api.weather.gov/alerts/active*' => Http::response(['features' => [['properties' => ['event' => 'Flash Flood Watch', 'severity' => 'Moderate', 'headline' => 'Flash Flood Watch until 9 PM']]]]),
        ]);

        $data = $this->tool('forecast', ['place' => ['area_id' => Area::KANAB], 'date' => '2026-07-10', 'from_hour' => 9, 'to_hour' => 17]);
        $this->assertSame(60, $data['summary']['max_rain_pct']);
        $this->assertSame('3 PM', $data['summary']['rainiest_hour']);
        $this->assertSame(87, $data['summary']['high_f']);
        $this->assertStringContainsString('Rain jacket or poncho', implode(' ', $data['gear_implied']));
        $this->assertStringContainsString('Flash Flood Watch', implode(' ', $data['gear_implied']));
    }

    public function test_forecast_beyond_a_week_never_invents_weather(): void
    {
        $data = $this->tool('forecast', ['place' => ['area_id' => Area::KANAB], 'date' => '2026-08-30']);
        $this->assertTrue($data['unavailable']);
        Http::assertNothingSent();
    }

    public function test_river_flow_reads_the_usgs_gauge_and_thresholds(): void
    {
        Http::fake(['api.waterdata.usgs.gov/*' => Http::response(['features' => [['properties' => ['value' => '131.0', 'time' => '2026-07-10T14:45:00+00:00']]]])]);
        $narrows = $this->insertTrail(['name' => 'The Narrows', 'area_id' => Area::ZION]);

        $data = $this->tool('river_flow', ['trail_id' => $narrows]);
        $this->assertSame(131.0, $data['cfs']);
        $this->assertContains('Top-down Narrows trips close above 120 CFS.', $data['over_thresholds']);
        $this->assertNotContains('The Narrows closes to all hiking above 150 CFS.', $data['over_thresholds']);
    }

    public function test_live_failures_are_reported_with_the_official_page(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $narrows = $this->insertTrail(['name' => 'The Narrows', 'area_id' => Area::ZION]);
        $data = $this->tool('river_flow', ['trail_id' => $narrows]);
        $this->assertTrue($data['unavailable']);
        $this->assertSame('https://www.nps.gov/zion/planyourvisit/thenarrows.htm', $data['check_instead']);
    }

    public function test_crowds_reports_busiest_and_quietest_hours(): void
    {
        $restaurant = $this->fixtureRestaurantIds[Area::KANAB][0];
        $location = DB::table('restaurants')->where('id', $restaurant)->value('location_id');
        foreach ([11 => 20, 12 => 80, 18 => 95, 20 => 40] as $hour => $pct) {
            DB::table('resource_busy')->insert(['resource_table' => 'locations', 'resource_id' => $location, 'collected_date' => '2025-03-01',
                'day' => 'Saturday', 'day_of_week' => 6, 'hour' => $hour, 'percentage' => $pct]);
        }
        $data = $this->tool('crowds', ['restaurant_id' => $restaurant, 'date' => '2026-07-11', 'hour' => 12]);
        $this->assertSame('6 PM', $data['busiest_hour']);
        $this->assertSame('11 AM', $data['quietest_open_hour']);
        $this->assertSame('80%', $data['at_requested_hour']);
    }

    public function test_events_between_dates(): void
    {
        $location = DB::table('locations')->insertGetId(['name' => 'Kanab Center', 'area_id' => Area::KANAB, 'is_usable' => 1]);
        $event = DB::table('events')->insertGetId(['name' => 'Balloons and Tunes', 'source_type' => 't', 'source_id' => 'b', 'location_id' => $location, 'free' => 1, 'is_usable' => 1,
            'start_date' => '2026-07-11 18:00:00', 'end_date' => '2026-07-11 21:00:00']);
        DB::table('event_dates')->insert(['event_id' => $event, 'start_date' => '2026-07-11 18:00:00', 'end_date' => '2026-07-11 21:00:00']);

        $data = $this->tool('events', ['from' => '2026-07-10', 'to' => '2026-07-12', 'area_id' => Area::KANAB]);
        $this->assertSame('Balloons and Tunes', $data['events'][0]['name']);
        $this->assertSame([], $this->tool('events', ['from' => '2026-07-13', 'to' => '2026-07-20'])['events']);
    }

    public function test_status_reports_seasonal_closure(): void
    {
        Area::where('id', Area::NORTH_RIM)->update(['closed_from' => '12-01', 'closed_until' => '05-14']);
        Http::fake(['*' => Http::response(['features' => []])]);
        $data = $this->tool('status', ['place' => ['area_id' => Area::NORTH_RIM], 'date' => '2026-12-15']);
        $this->assertTrue($data['area_seasonally_closed']);
        $this->assertSame('Closed every year from December 1 to May 14.', $data['area_season']);
    }

    public function test_show_map_and_offer_new_itinerary_attach_components(): void
    {
        $trail = $this->fixtureTrailIds[Area::ZION][0];
        $map = $this->tools->get('show_map')->handle(['trail_id' => $trail], $this->context);
        $this->assertSame('buttons/ViewMap', $map->component);
        $this->assertSame($trail, $map->componentParams['resource']['id']);
        $this->assertFalse($map->endsTurn);

        $offer = $this->tools->get('offer_new_itinerary')->handle(['request' => 'easier hikes, less driving'], $this->context);
        $this->assertSame('buttons/VerifySpecializedItinerary', $offer->component);
        $this->assertSame(['query' => 'easier hikes, less driving', 'replaceItinerary' => false], $offer->componentParams);
        $this->assertTrue($offer->endsTurn);
    }

    public function test_itinerary_tools_need_an_itinerary_and_permission(): void
    {
        $this->assertTrue($this->tool('get_itinerary', [])['unavailable']);
        $this->assertTrue($this->tool('edit_itinerary', ['action' => 'remove', 'activity_id' => 'x'])['unavailable']);
    }
}
