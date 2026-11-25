<?php

namespace App\Chat\Live;

/**
 * National Weather Service (api.weather.gov): hourly and 12-hour forecasts for a point, about 7 days out, and active
 * watches and warnings. Free, no key, but a User-Agent is required.
 */
class NwsClient extends LiveClient
{
    public const BASE = 'https://api.weather.gov';
    public const PAGE = 'https://forecast.weather.gov/MapClick.php';

    private function headers(): array
    {
        return ['User-Agent' => (string) config('chat.sources.nws_user_agent'), 'Accept' => 'application/geo+json'];
    }

    /** @return array{hourly: ?string, daily: ?string}|null */
    public function gridpoint(float $lat, float $lng): ?array
    {
        $data = $this->getJson(sprintf('%s/points/%.4f,%.4f', self::BASE, $lat, $lng), [], 24 * 60, $this->headers());
        if (!$data || empty($data['properties']['forecastHourly'])) {
            return null;
        }
        return ['hourly' => $data['properties']['forecastHourly'], 'daily' => $data['properties']['forecast'] ?? null];
    }

    /** Hourly periods (startTime, temperature, probabilityOfPrecipitation, windSpeed, shortForecast...). */
    public function hourly(float $lat, float $lng): ?array
    {
        $grid = $this->gridpoint($lat, $lng);
        $data = $grid ? $this->getJson($grid['hourly'], [], self::minutes('forecast'), $this->headers()) : null;
        return $data['properties']['periods'] ?? null;
    }

    /** Day and night periods with the detailed text forecast. */
    public function daily(float $lat, float $lng): ?array
    {
        $grid = $this->gridpoint($lat, $lng);
        $data = $grid && $grid['daily'] ? $this->getJson($grid['daily'], [], self::minutes('forecast'), $this->headers()) : null;
        return $data['properties']['periods'] ?? null;
    }

    /** Active alerts (flash flood, winter storm, heat, red flag...) covering the point. */
    public function alerts(float $lat, float $lng): ?array
    {
        $data = $this->getJson(self::BASE . '/alerts/active', ['point' => sprintf('%.4f,%.4f', $lat, $lng)], self::minutes('alerts'), $this->headers());
        if ($data === null) {
            return null;
        }
        return array_map(fn($feature) => [
            'event' => $feature['properties']['event'] ?? null,
            'severity' => $feature['properties']['severity'] ?? null,
            'headline' => $feature['properties']['headline'] ?? null,
            'starts' => $feature['properties']['onset'] ?? $feature['properties']['effective'] ?? null,
            'ends' => $feature['properties']['ends'] ?? $feature['properties']['expires'] ?? null,
            'instruction' => isset($feature['properties']['instruction']) ? mb_substr($feature['properties']['instruction'], 0, 400) : null,
        ], $data['features'] ?? []);
    }

    public static function pageFor(float $lat, float $lng): string
    {
        return self::PAGE . '?' . http_build_query(['lat' => round($lat, 4), 'lon' => round($lng, 4)]);
    }
}
