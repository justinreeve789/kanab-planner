<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\RoadEvent;
use App\Services\RoadEvents\Five11Feed;
use App\Services\Routing\EstimateRouter;
use App\Services\Routing\Leg;
use App\Services\Routing\NoRouteFound;
use App\Services\Routing\Point;
use App\Services\Routing\RouterUnavailable;
use App\Services\Routing\TravelTimes;
use App\Services\Routing\ValhallaRouter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Drive times: the Valhalla client, caching and fallbacks, road events from the 511 feeds, and re-timing drives
 * after an edit. No network: Valhalla and the feeds are faked.
 */
class RoutingTest extends TestCase
{
    use ItineraryAssertions;
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private const VALHALLA = 'http://valhalla.test:8002';

    private Point $kanab;
    private Point $springdale;
    /** A straight road from Kanab to Springdale, sampled every few miles. */
    private array $road;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kanab = new Point(37.0475, -112.5263);
        $this->springdale = new Point(37.1889, -112.9986);
        $this->road = [];
        for ($i = 0; $i <= 10; $i++) {
            $this->road[] = new Point(
                $this->kanab->lat + ($this->springdale->lat - $this->kanab->lat) * $i / 10,
                $this->kanab->lng + ($this->springdale->lng - $this->kanab->lng) * $i / 10,
            );
        }
        Cache::flush();
    }

    private function useValhalla(): TravelTimes
    {
        config(['routing.driver' => 'valhalla', 'routing.valhalla.url' => self::VALHALLA]);
        return app(TravelTimes::class);
    }

    private function trip(int $seconds = 2880, float $miles = 41.2, ?array $shape = null): array
    {
        return ['trip' => [
            'summary' => ['time' => $seconds, 'length' => $miles],
            'legs' => [['shape' => self::encode($shape ?? $this->road)]],
        ]];
    }

    public function test_valhalla_request_and_response(): void
    {
        Http::fake([self::VALHALLA . '/route' => Http::response($this->trip())]);
        $router = new ValhallaRouter(self::VALHALLA);
        $departAt = CarbonImmutable::parse('2027-06-02 07:30', 'America/Denver');

        $leg = $router->route($this->kanab, $this->springdale, $departAt, [new Point(37.1, -112.7)]);

        $this->assertSame(48, $leg->minutes);
        $this->assertSame(41.2, $leg->miles);
        $this->assertSame(Leg::SOURCE_ROUTER, $leg->source);
        $this->assertCount(11, $leg->shape);
        $this->assertEqualsWithDelta($this->springdale->lng, $leg->shape[10]->lng, 0.000001);

        Http::assertSent(function(HttpRequest $request) {
            $body = $request->data();
            return $request->url() === self::VALHALLA . '/route'
                && $body['costing'] === 'auto'
                && $body['units'] === 'miles'
                && $body['locations'][0] === ['lat' => 37.0475, 'lon' => -112.5263]
                && $body['date_time'] === ['type' => 1, 'value' => '2027-06-02T07:30']
                && $body['exclude_locations'] === [['lat' => 37.1, 'lon' => -112.7]];
        });
    }

    public function test_valhalla_errors(): void
    {
        $router = new ValhallaRouter(self::VALHALLA);
        Http::fake([self::VALHALLA . '/route' => Http::sequence()
            ->push(['error_code' => 442, 'error' => 'No path could be found for input'], 400)
            ->push(['error_code' => 171, 'error' => 'No suitable edges near location'], 400)
            ->push('Internal Server Error', 500)]);

        $this->assertThrows(fn() => $router->route($this->kanab, $this->springdale), NoRouteFound::class);
        $this->assertThrows(fn() => $router->route($this->kanab, $this->springdale), RouterUnavailable::class, 'Valhalla 400: No suitable edges');
        $this->assertThrows(fn() => $router->route($this->kanab, $this->springdale), RouterUnavailable::class, 'Valhalla 500');
    }

    public function test_polylines_decode(): void
    {
        // Google's documented example, at five decimal places.
        $points = ValhallaRouter::decodePolyline('_p~iF~ps|U_ulLnnqC_mqNvxq`@', 5);
        $this->assertEqualsWithDelta([38.5, -120.2], [$points[0]->lat, $points[0]->lng], 0.00001);
        $this->assertEqualsWithDelta([43.252, -126.453], [$points[2]->lat, $points[2]->lng], 0.00001);
    }

    public function test_routes_are_cached_and_nearby_stops_need_no_drive(): void
    {
        Http::fake([self::VALHALLA . '/route' => Http::response($this->trip())]);
        $travel = $this->useValhalla();

        $this->assertSame(48, $travel->leg($this->kanab, $this->springdale)->minutes);
        $this->assertSame(48, $travel->leg($this->kanab, $this->springdale)->minutes);
        Http::assertSentCount(1);

        $nextDoor = new Point($this->kanab->lat + 0.001, $this->kanab->lng);
        $this->assertSame(0, $travel->leg($this->kanab, $nextDoor)->minutes);
        Http::assertSentCount(1);
    }

    public function test_falls_back_to_estimates(): void
    {
        $estimate = (new EstimateRouter())->route($this->kanab, $this->springdale);
        $valhalla = 'unplaceable';
        Http::fake(function() use (&$valhalla) {
            return match ($valhalla) {
                'unplaceable' => Http::response(['error_code' => 171, 'error' => 'No suitable edges'], 400),
                'down' => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
                default => Http::response($this->trip()),
            };
        });
        $travel = $this->useValhalla();

        // A point Valhalla can't put on a road: estimate this leg, keep using the router.
        $leg = $travel->leg($this->kanab, $this->springdale);
        $this->assertSame(Leg::SOURCE_ESTIMATE, $leg->source);
        $this->assertSame($estimate->minutes, $leg->minutes);
        $this->assertFalse(Cache::has('routing:router-down'));

        // Valhalla is down: estimate, and stop asking for a while.
        $valhalla = 'down';
        $this->assertSame(Leg::SOURCE_ESTIMATE, $travel->leg($this->springdale, $this->kanab)->source);
        $this->assertTrue(Cache::has('routing:router-down'));
        $valhalla = 'up';
        $this->assertSame(Leg::SOURCE_ESTIMATE, $travel->leg(new Point(37.1, -112.6), $this->springdale)->source);
        Http::assertSentCount(1); // Only the 171; the refused connection never got a response.

        // ... and back to the router once the wait is over.
        Cache::forget('routing:router-down');
        $this->assertSame(Leg::SOURCE_ROUTER, $travel->leg(new Point(37.1, -112.6), $this->springdale)->source);
    }

    public function test_roadwork_on_the_route_adds_a_delay_and_a_note(): void
    {
        Http::fake([self::VALHALLA . '/route' => Http::response($this->trip())]);
        $onRoute = $this->road[5];
        $this->roadEvent(['event_type' => RoadEvent::ROADWORK, 'roadway' => 'US-89', 'description' => 'Paving, one lane open',
            'latitude' => $onRoute->lat + 0.001, 'longitude' => $onRoute->lng, 'starts_at' => '2027-06-01 00:00:00', 'ends_at' => '2027-06-30 00:00:00']);
        // Off the route, and roadwork on the route that has already finished.
        $this->roadEvent(['event_type' => RoadEvent::ROADWORK, 'latitude' => 37.6, 'longitude' => -112.2, 'starts_at' => '2027-06-01 00:00:00', 'ends_at' => '2027-06-30 00:00:00']);
        $this->roadEvent(['event_type' => RoadEvent::ROADWORK, 'latitude' => $onRoute->lat, 'longitude' => $onRoute->lng, 'starts_at' => '2027-05-01 00:00:00', 'ends_at' => '2027-05-20 00:00:00']);

        $leg = $this->useValhalla()->leg($this->kanab, $this->springdale, CarbonImmutable::parse('2027-06-02 08:00', 'America/Denver'));

        $this->assertSame(48 + 10, $leg->minutes);
        $this->assertCount(1, $leg->notes);
        $this->assertSame('Roadwork on US-89: Paving, one lane open', $leg->notes[0]['text']);
        $this->assertFalse($leg->notes[0]['closure']);
    }

    public function test_a_closure_on_the_route_is_routed_around(): void
    {
        $detour = [$this->kanab, new Point(37.3, -112.6), $this->springdale];
        Http::fake(function(HttpRequest $request) use ($detour) {
            return isset($request->data()['exclude_locations'])
                ? Http::response($this->trip(5400, 70.4, $detour))
                : Http::response($this->trip());
        });
        $closed = $this->road[3];
        $this->roadEvent(['event_type' => RoadEvent::CLOSURES, 'is_full_closure' => true, 'roadway' => 'US-89',
            'latitude' => $closed->lat, 'longitude' => $closed->lng, 'starts_at' => '2027-06-01 00:00:00', 'ends_at' => '2027-06-03 00:00:00']);

        $leg = $this->useValhalla()->leg($this->kanab, $this->springdale, CarbonImmutable::parse('2027-06-02 08:00', 'America/Denver'));

        $this->assertSame(90, $leg->minutes);
        $this->assertSame(70.4, $leg->miles);
        $this->assertStringStartsWith('Detour around a closure. Road closed on US-89', $leg->notes[0]['text']);
        $this->assertTrue($leg->notes[0]['closure']);

        // The day after, the road is open again.
        $this->assertSame(48, $this->useValhalla()->leg($this->kanab, $this->springdale, CarbonImmutable::parse('2027-06-04 08:00', 'America/Denver'))->minutes);
    }

    public function test_a_closure_with_no_way_around_is_reported(): void
    {
        Http::fake(fn(HttpRequest $request) => isset($request->data()['exclude_locations'])
            ? Http::response(['error_code' => 442, 'error' => 'No path could be found for input'], 400)
            : Http::response($this->trip()));
        $closed = $this->road[3];
        $this->roadEvent(['event_type' => RoadEvent::CLOSURES, 'is_full_closure' => true, 'roadway' => 'SR-9',
            'latitude' => $closed->lat, 'longitude' => $closed->lng, 'starts_at' => null, 'ends_at' => '2027-06-03 00:00:00']);

        $leg = $this->useValhalla()->leg($this->kanab, $this->springdale, CarbonImmutable::parse('2027-06-02 08:00', 'America/Denver'));

        $this->assertSame(48, $leg->minutes);
        $texts = array_column($leg->notes, 'text');
        $this->assertStringContainsString('no way around it', $texts[0]);
        $this->assertSame('Road closed on SR-9', $texts[1]);
    }

    public function test_incidents_without_an_end_only_count_for_the_next_few_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-06-01 12:00', 'America/Denver'));
        $event = $this->roadEvent(['event_type' => RoadEvent::INCIDENTS, 'latitude' => 37.1, 'longitude' => -112.7,
            'starts_at' => '2027-06-01 15:00:00', 'ends_at' => null]);

        $this->assertTrue(RoadEvent::activeOn('2027-06-02')->whereKey($event->id)->exists());
        $this->assertTrue(RoadEvent::activeOn('2027-06-03')->whereKey($event->id)->exists());
        $this->assertFalse(RoadEvent::activeOn('2027-06-10')->whereKey($event->id)->exists());
        $this->assertFalse(RoadEvent::activeOn('2027-05-31')->whereKey($event->id)->exists(), 'Not before it started.');
    }

    public function test_without_a_router_only_events_by_either_end_are_flagged(): void
    {
        $travel = app(TravelTimes::class);
        $this->assertFalse($travel->usesRouter());
        $this->roadEvent(['event_type' => RoadEvent::ROADWORK, 'roadway' => 'SR-9', 'latitude' => $this->springdale->lat + 0.005,
            'longitude' => $this->springdale->lng, 'starts_at' => '2027-06-01 00:00:00', 'ends_at' => '2027-06-30 00:00:00']);
        $this->roadEvent(['event_type' => RoadEvent::ROADWORK, 'latitude' => $this->road[5]->lat, 'longitude' => $this->road[5]->lng,
            'starts_at' => '2027-06-01 00:00:00', 'ends_at' => '2027-06-30 00:00:00']);

        $leg = $travel->leg($this->kanab, $this->springdale, CarbonImmutable::parse('2027-06-02 08:00', 'America/Denver'));
        $this->assertSame(['Roadwork on SR-9'], array_column($leg->notes, 'text'));
    }

    public function test_511_feeds_are_read_and_kept_current(): void
    {
        config(['routing.road_events.sources.udot.key' => 'test-key']);
        $feed = [
            ['ID' => 'UDOT-1', 'EventType' => 'roadwork', 'IsFullClosure' => false, 'RoadwayName' => 'SR-9',
                'Description' => '<p>Tunnel escorts for large vehicles</p>', 'Latitude' => 37.2133, 'Longitude' => -112.9434,
                'LatitudeSecondary' => 0, 'LongitudeSecondary' => 0, 'StartDate' => 1811808000, 'PlannedEndDate' => 1814400000],
            ['ID' => 'UDOT-2', 'EventType' => 'closures', 'IsFullClosure' => true, 'RoadwayName' => 'SR-14',
                'Latitude' => 37.62, 'Longitude' => -112.85, 'LatitudeSecondary' => 37.6, 'LongitudeSecondary' => -112.7, 'Reported' => 1811808000],
            ['ID' => 'UDOT-3', 'EventType' => 'roadwork', 'RoadwayName' => 'I-15 in Salt Lake City', 'Latitude' => 40.76, 'Longitude' => -111.89],
            ['ID' => 'UDOT-4', 'EventType' => 'accidentsAndIncidents'],
        ];
        $feedUp = true;
        Http::fake(function() use (&$feedUp, $feed) {
            return $feedUp ? Http::response($feed) : Http::response('Invalid Key', 401);
        });
        RoadEvent::create(['source' => 'udot', 'external_id' => 'OLD', 'event_type' => 'roadwork', 'latitude' => 37, 'longitude' => -112.5]);

        $this->artisan('update:road-events')->assertSuccessful();

        Http::assertSent(fn(HttpRequest $r) => str_starts_with($r->url(), 'https://udottraffic.utah.gov/api/v2/get/event')
            && $r['key'] === 'test-key' && $r['format'] === 'json');
        $this->assertEqualsCanonicalizing(['UDOT-1', 'UDOT-2'], RoadEvent::pluck('external_id')->all(), 'Salt Lake City is outside the region; OLD has ended.');

        $tunnel = RoadEvent::where('external_id', 'UDOT-1')->first();
        $this->assertSame('Tunnel escorts for large vehicles', $tunnel->description);
        $this->assertNull($tunnel->latitude_secondary);
        $this->assertSame('2027-06-01 00:00:00', $tunnel->starts_at->format('Y-m-d H:i:s'));
        $closure = RoadEvent::where('external_id', 'UDOT-2')->first();
        $this->assertTrue($closure->is_full_closure);
        $this->assertCount(2, $closure->points());
        $this->assertNull($closure->ends_at);

        // A failing feed keeps what it had.
        $feedUp = false;
        $this->artisan('update:road-events')->assertFailed();
        $this->assertSame(2, RoadEvent::count());
    }

    public function test_the_feed_command_is_quiet_without_keys(): void
    {
        Http::fake();
        $this->artisan('update:road-events')->expectsOutputToContain('No road event feeds are configured')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_feed_types_are_normalized(): void
    {
        $feed = new Five11Feed('az511', 'https://az511.example/api', 'k');
        $this->assertSame(RoadEvent::INCIDENTS, $feed->normalize(['ID' => 1, 'EventType' => 'AccidentsAndIncidents', 'Latitude' => 36.9, 'Longitude' => -111.5])['event_type']);
        $this->assertSame(RoadEvent::SPECIAL_EVENTS, $feed->normalize(['ID' => 2, 'EventType' => 'specialEvents', 'Latitude' => 36.9, 'Longitude' => -111.5])['event_type']);
        $this->assertNull($feed->normalize(['ID' => 3, 'EventType' => 'roadwork']), 'No location.');
    }

    public function test_drives_are_retimed_after_an_edit_and_short_gaps_flagged(): void
    {
        $this->seedKanabFixtures();
        $itinerary = $this->postJson('/api/v1/itinerary', ['base_area_id' => Area::KANAB, 'start_date' => '2027-06-01', 'duration_days' => 3])->json();
        $token = $itinerary['edit_token'];
        $day = $itinerary['days'][1];

        // Add a Zion trail right after breakfast: Kanab to Zion takes most of an hour.
        $zionTrail = $this->fixtureTrailIds[Area::ZION][0];
        $breakfastEnd = collect($this->activitiesOfDay($day))->pluck('activity')->firstWhere('name', 'Breakfast')['end_time'];
        $this->postJson("/api/v1/day/{$day['id']}/activity", [
            'resource_table' => 'trails', 'resource_id' => $zionTrail, 'start_time' => substr($breakfastEnd, 0, 5),
        ], ['X-Itinerary-Token' => $token])->assertCreated();

        $fresh = $this->getJson("/api/v1/day/{$day['id']}")->assertOk()->json();
        $hike = collect($this->activitiesOfDay($fresh))->pluck('activity')->first(fn($a) => $a['resource_table'] === 'trails' && $a['resource_id'] === $zionTrail);
        $this->assertGreaterThan(30, $hike['travel_minutes']);
        $this->assertSame(substr($breakfastEnd, 0, 5), substr($hike['start_time'], 0, 5), 'Edits keep the times you chose.');
        $this->assertSame('tight', $hike['travel_notes'][0]['type']);
        $this->assertStringContainsString('late', $hike['travel_notes'][0]['text']);
        $this->assertGreaterThan(0, $fresh['drive_minutes']);
    }

    private function roadEvent(array $attributes): RoadEvent
    {
        static $n = 0;
        return RoadEvent::create($attributes + ['source' => 'udot', 'external_id' => 'E' . (++$n), 'is_full_closure' => false]);
    }

    /** Google's polyline algorithm at six decimal places (Valhalla's shape format). */
    private static function encode(array $points): string
    {
        $out = '';
        $prevLat = $prevLng = 0;
        foreach ($points as $point) {
            foreach ([[(int) round($point->lat * 1e6), &$prevLat], [(int) round($point->lng * 1e6), &$prevLng]] as [$value, &$prev]) {
                $delta = $value - $prev;
                $prev = $value;
                $delta = $delta < 0 ? ~($delta << 1) : ($delta << 1);
                while ($delta >= 0x20) {
                    $out .= chr((0x20 | ($delta & 0x1f)) + 63);
                    $delta >>= 5;
                }
                $out .= chr($delta + 63);
            }
        }
        return $out;
    }
}
