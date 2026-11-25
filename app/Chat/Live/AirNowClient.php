<?php

namespace App\Chat\Live;

/**
 * EPA AirNow forecasts by location (AIRNOW_API_KEY): daily AQI category and pollutant, e.g. smoke as PM2.5.
 */
class AirNowClient extends LiveClient
{
    public const BASE = 'https://www.airnowapi.org/aq/forecast/latLong/';
    public const PAGE = 'https://www.airnow.gov/';

    public function enabled(): bool
    {
        return (bool) config('chat.sources.airnow_api_key');
    }

    public function forecast(float $lat, float $lng, string $date): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        $data = $this->getJson(self::BASE, [
            'format' => 'application/json',
            'latitude' => round($lat, 4),
            'longitude' => round($lng, 4),
            'date' => $date,
            'distance' => 75,
            'API_KEY' => config('chat.sources.airnow_api_key'),
        ], self::minutes('air_quality'));
        if ($data === null) {
            return null;
        }
        return array_map(fn($row) => [
            'date' => $row['DateForecast'] ?? null,
            'area' => $row['ReportingArea'] ?? null,
            'pollutant' => $row['ParameterName'] ?? null,
            'aqi' => ($row['AQI'] ?? -1) >= 0 ? $row['AQI'] : null,
            'category' => $row['Category']['Name'] ?? null,
            'discussion' => mb_substr($row['Discussion'] ?? '', 0, 300),
        ], $data);
    }
}
