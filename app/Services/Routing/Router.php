<?php

namespace App\Services\Routing;

use Carbon\CarbonInterface;

interface Router
{
    /**
     * The drive from $from to $to, avoiding the $avoid points (full road closures).
     *
     * @param Point[] $avoid
     * @throws RouterUnavailable when the router can't be reached or can't place the points on a road
     * @throws NoRouteFound when there is no way through (usually because of a closure)
     */
    public function route(Point $from, Point $to, ?CarbonInterface $departAt = null, array $avoid = []): Leg;
}
