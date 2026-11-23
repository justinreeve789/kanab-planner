<?php

namespace App\Services\Routing;

use App\Models\RoadEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Drive times between two places on a given day.
 *
 * Routes come from the configured router and are cached; when the router is off, unreachable or can't place a
 * point on a road, the leg is estimated from straight-line distance instead. Road events from the 511 systems are
 * then applied for the day of travel: full closures are routed around (or reported when there's no way through),
 * and anything else on the route adds a delay and a note.
 */
class TravelTimes
{
    private const CACHE_PREFIX = 'route:v1:';
    private const ROUTER_DOWN_KEY = 'routing:router-down';
    /** Cached shapes are thinned to about this many points. */
    private const MAX_CACHED_SHAPE_POINTS = 400;

    public function __construct(private Router $router, private EstimateRouter $estimator)
    {
    }

    public static function make(): self
    {
        return app(self::class);
    }

    public function leg(Point $from, Point $to, ?CarbonInterface $departAt = null): Leg
    {
        if ($from->milesTo($to) < config('routing.walkable_miles')) {
            return Leg::none();
        }

        $leg = $this->routed($from, $to, $departAt);
        if (!$departAt) {
            return $leg;
        }
        $date = $departAt->toDateString();
        return $this->applyRoadEvents($this->avoidClosures($leg, $from, $to, $departAt, $date), $from, $to, $date);
    }

    public function usesRouter(): bool
    {
        return !$this->router instanceof EstimateRouter;
    }

    /**
     * @param Point[] $avoid
     * @param int[] $avoidIds the road events behind $avoid, for the cache key
     */
    private function routed(Point $from, Point $to, ?CarbonInterface $departAt, array $avoid = [], array $avoidIds = []): Leg
    {
        if (!$this->usesRouter() || Cache::has(self::ROUTER_DOWN_KEY)) {
            return $this->estimator->route($from, $to);
        }

        $key = self::CACHE_PREFIX . $from->key() . '>' . $to->key() . ($avoidIds ? ':avoid:' . implode(',', $avoidIds) : '');
        if ($cached = Cache::get($key)) {
            return self::fromCache($cached);
        }

        try {
            $leg = $this->router->route($from, $to, $departAt, $avoid);
        } catch (NoRouteFound $e) {
            if (!$avoid) {
                return $this->estimator->route($from, $to);
            }
            // Closed with no way around: route as if open, and say so.
            $leg = $this->routed($from, $to, $departAt)->withNote([
                'type' => RoadEvent::CLOSURES, 'closure' => true,
                'text' => 'A road on this drive is closed and there\'s no way around it. Check conditions before you go.',
            ]);
            Cache::put($key, self::toCache($leg), now()->addHour());
            return $leg;
        } catch (RouterUnavailable $e) {
            if ($e->getPrevious() instanceof \Illuminate\Http\Client\ConnectionException) {
                Log::warning('Router unreachable; using estimated drive times for a while.', ['error' => $e->getMessage()]);
                Cache::put(self::ROUTER_DOWN_KEY, true, config('routing.retry_after_seconds'));
            }
            return $this->estimator->route($from, $to);
        }

        // Routes don't change with the departure time (there's no traffic data), so one answer serves every trip.
        Cache::put($key, self::toCache($leg), now()->addDays(config('routing.cache_days')));
        return $leg;
    }

    /**
     * Full closures on the usual route: with a router, find the way around them; without one, just warn.
     */
    private function avoidClosures(Leg $leg, Point $from, Point $to, CarbonInterface $departAt, string $date): Leg
    {
        $blocking = RoadEvent::query()->where('is_full_closure', true)->activeOn($date)->around($from, $to, 5)->orderBy('id')->get()
            ->filter(fn(RoadEvent $event) => $this->affects($event, $leg, $from, $to))
            ->values();
        if ($blocking->isEmpty()) {
            return $leg;
        }

        $prefix = '';
        if ($leg->source === Leg::SOURCE_ROUTER) {
            $leg = $this->routed($from, $to, $departAt,
                $blocking->map(fn(RoadEvent $event) => $event->points()[0])->all(), $blocking->pluck('id')->all());
            // routed() notes when there's no way around; otherwise this is a detour.
            $noWayThrough = collect($leg->notes)->contains(fn($note) => $note['closure']);
            $prefix = $noWayThrough ? '' : 'Detour around a closure. ';
        }
        foreach ($blocking as $event) {
            $leg = $leg->withNote(['type' => RoadEvent::CLOSURES, 'closure' => true, 'text' => $prefix . $event->summary()]);
        }
        return $leg;
    }

    /** Roadwork, lane closures and incidents on the route each add a delay. */
    private function applyRoadEvents(Leg $leg, Point $from, Point $to, string $date): Leg
    {
        $events = RoadEvent::query()->where('is_full_closure', false)->activeOn($date)->around($from, $to, 5)->orderBy('id')->get();
        foreach ($events as $event) {
            if ($this->affects($event, $leg, $from, $to)) {
                $delay = (int) (config('routing.road_events.delay_minutes.' . $event->event_type) ?? 10);
                $leg = $leg->withDelay($delay, ['type' => $event->event_type, 'closure' => false, 'text' => $event->summary()]);
            }
        }
        return $leg;
    }

    private function affects(RoadEvent $event, Leg $leg, Point $from, Point $to): bool
    {
        if ($leg->source === Leg::SOURCE_ROUTER && count($leg->shape) > 1) {
            return $this->isNearShape($event, $leg->shape, config('routing.road_events.near_route_miles'));
        }
        // Without a real route we only know the ends of the drive, so only flag events right by them.
        return $this->isNearShape($event, [$from], 1.0) || $this->isNearShape($event, [$to], 1.0);
    }

    /** @param Point[] $shape */
    private function isNearShape(RoadEvent $event, array $shape, float $miles): bool
    {
        foreach ($event->points() as $point) {
            if (count($shape) === 1) {
                if ($point->milesTo($shape[0]) <= $miles) {
                    return true;
                }
                continue;
            }
            for ($i = 1, $n = count($shape); $i < $n; $i++) {
                if ($point->milesToSegment($shape[$i - 1], $shape[$i]) <= $miles) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function toCache(Leg $leg): array
    {
        $shape = $leg->shape;
        $step = max(1, (int) ceil(count($shape) / self::MAX_CACHED_SHAPE_POINTS));
        $thinned = [];
        foreach ($shape as $i => $point) {
            if ($i % $step === 0 || $i === count($shape) - 1) {
                $thinned[] = [$point->lat, $point->lng];
            }
        }
        return $leg->toArray() + ['shape' => $thinned];
    }

    private static function fromCache(array $cached): Leg
    {
        return new Leg(
            $cached['minutes'],
            $cached['miles'],
            $cached['source'],
            array_map(fn($p) => new Point($p[0], $p[1]), $cached['shape'] ?? []),
            $cached['notes'] ?? [],
        );
    }
}
