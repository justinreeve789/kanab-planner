<?php

namespace App\Services\Routing;

use Carbon\CarbonInterface;

/**
 * Drive time from straight-line distance, for when there's no router (and as its fallback).
 */
class EstimateRouter implements Router
{
    public function __construct(private float $roadFactor = 1.3, private float $mph = 45)
    {
    }

    public function route(Point $from, Point $to, ?CarbonInterface $departAt = null, array $avoid = []): Leg
    {
        $miles = round($from->milesTo($to) * $this->roadFactor, 1);
        return new Leg((int) round($miles / $this->mph * 60), $miles, Leg::SOURCE_ESTIMATE, [$from, $to]);
    }
}
