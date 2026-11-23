<?php

namespace App\Services\Routing;

/**
 * A place on the map, in decimal degrees.
 */
final class Point
{
    public function __construct(public readonly float $lat, public readonly float $lng)
    {
    }

    public static function make($lat, $lng): ?self
    {
        return is_numeric($lat) && is_numeric($lng) && ((float) $lat !== 0.0 || (float) $lng !== 0.0)
            ? new self((float) $lat, (float) $lng)
            : null;
    }

    /** Great-circle distance in miles. */
    public function milesTo(Point $other): float
    {
        $dLat = deg2rad($other->lat - $this->lat);
        $dLng = deg2rad($other->lng - $this->lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->lat)) * cos(deg2rad($other->lat)) * sin($dLng / 2) ** 2;
        return 3958.8 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Distance in miles from this point to the segment a-b (flat-earth, fine at these distances). */
    public function milesToSegment(Point $a, Point $b): float
    {
        $kx = 69.17 * cos(deg2rad($this->lat));
        $ky = 69.05;
        [$ax, $ay] = [($a->lng - $this->lng) * $kx, ($a->lat - $this->lat) * $ky];
        [$bx, $by] = [($b->lng - $this->lng) * $kx, ($b->lat - $this->lat) * $ky];
        [$dx, $dy] = [$bx - $ax, $by - $ay];
        $lengthSquared = $dx * $dx + $dy * $dy;
        $t = $lengthSquared > 0 ? max(0, min(1, -($ax * $dx + $ay * $dy) / $lengthSquared)) : 0;
        return hypot($ax + $t * $dx, $ay + $t * $dy);
    }

    public function key(): string
    {
        return sprintf('%.4f,%.4f', $this->lat, $this->lng);
    }
}
