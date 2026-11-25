<?php

namespace App\Chat\Live;

/**
 * USGS Water Data API (api.waterdata.usgs.gov), which replaces the WaterServices "iv" service being retired in early
 * 2027. Reads the latest discharge (parameter 00060, cubic feet per second) for a monitoring location.
 */
class UsgsClient extends LiveClient
{
    public const BASE = 'https://api.waterdata.usgs.gov/ogcapi/v1/collections/latest-continuous/items';
    public const DISCHARGE = '00060';

    /** @return array{cfs: float, time: string}|null */
    public function latestDischarge(string $site): ?array
    {
        $headers = config('chat.sources.usgs_api_key') ? ['X-Api-Key' => config('chat.sources.usgs_api_key')] : [];
        $data = $this->getJson(self::BASE, [
            'monitoring_location_id' => $site,
            'parameter_code' => self::DISCHARGE,
            'f' => 'json',
        ], self::minutes('river_flow'), $headers);

        foreach ($data['features'] ?? [] as $feature) {
            $properties = $feature['properties'] ?? [];
            if (isset($properties['value']) && is_numeric($properties['value'])) {
                return ['cfs' => (float) $properties['value'], 'time' => (string) ($properties['time'] ?? '')];
            }
        }
        return null;
    }

    public static function pageFor(string $site): string
    {
        return 'https://waterdata.usgs.gov/monitoring-location/' . $site . '/';
    }
}
