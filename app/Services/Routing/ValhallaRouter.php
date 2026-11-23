<?php

namespace App\Services\Routing;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Car routing over OpenStreetMap roads from a Valhalla server (https://github.com/valhalla/valhalla).
 */
class ValhallaRouter implements Router
{
    /** Valhalla's "no path could be found" errors; anything else means it couldn't answer. */
    private const NO_ROUTE_ERRORS = [442, 443];

    public function __construct(private string $url, private float $timeout = 3)
    {
    }

    public function route(Point $from, Point $to, ?CarbonInterface $departAt = null, array $avoid = []): Leg
    {
        $request = [
            'locations' => [
                ['lat' => $from->lat, 'lon' => $from->lng],
                ['lat' => $to->lat, 'lon' => $to->lng],
            ],
            'costing' => 'auto',
            'units' => 'miles',
            'directions_type' => 'none',
        ];
        if ($departAt) {
            // Local time; Valhalla uses it for time-dependent restrictions (and traffic, if it has any).
            $request['date_time'] = ['type' => 1, 'value' => $departAt->format('Y-m-d\TH:i')];
        }
        if ($avoid) {
            $request['exclude_locations'] = array_map(fn(Point $p) => ['lat' => $p->lat, 'lon' => $p->lng], $avoid);
        }

        try {
            $response = Http::timeout($this->timeout)->acceptJson()->post(rtrim($this->url, '/') . '/route', $request);
        } catch (ConnectionException $e) {
            throw new RouterUnavailable($e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $code = (int) $response->json('error_code');
            if (in_array($code, self::NO_ROUTE_ERRORS, true)) {
                throw new NoRouteFound($response->json('error') ?? 'No route');
            }
            throw new RouterUnavailable("Valhalla {$response->status()}: " . ($response->json('error') ?? $response->body()));
        }

        $summary = $response->json('trip.summary');
        if (!is_array($summary) || !isset($summary['time'], $summary['length'])) {
            throw new RouterUnavailable('Valhalla returned no trip summary.');
        }

        $shape = [];
        foreach ($response->json('trip.legs') ?? [] as $leg) {
            if (!empty($leg['shape'])) {
                $shape = [...$shape, ...self::decodePolyline($leg['shape'])];
            }
        }

        return new Leg(
            (int) round($summary['time'] / 60),
            round((float) $summary['length'], 1),
            Leg::SOURCE_ROUTER,
            $shape,
        );
    }

    /**
     * Decode a Valhalla shape (Google's polyline algorithm at 6 decimal places).
     *
     * @return Point[]
     */
    public static function decodePolyline(string $encoded, int $precision = 6): array
    {
        $points = [];
        $index = $lat = $lng = 0;
        $factor = 10 ** $precision;
        $length = strlen($encoded);
        while ($index < $length) {
            foreach (['lat', 'lng'] as $axis) {
                $result = $shift = 0;
                do {
                    $byte = ord($encoded[$index++]) - 63;
                    $result |= ($byte & 0x1f) << $shift;
                    $shift += 5;
                } while ($byte >= 0x20 && $index < $length);
                $delta = ($result & 1) ? ~($result >> 1) : ($result >> 1);
                $$axis += $delta;
            }
            $points[] = new Point($lat / $factor, $lng / $factor);
        }
        return $points;
    }
}
