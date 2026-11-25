<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Live\NwsClient;
use App\Models\AreaWeather;
use Carbon\CarbonImmutable;

class Forecast extends BaseTool
{
    public const FORECAST_DAYS = 7;

    public function __construct(private NwsClient $nws)
    {
    }

    public function name(): string
    {
        return 'forecast';
    }

    public function description(): string
    {
        return 'Weather for a place and date from the National Weather Service: hour-by-hour temperature, chance of '
            . 'rain, wind and conditions for a time window, the day\'s forecast text, active watches and warnings '
            . '(flash flood, heat, winter storm), and gear it implies (rain shell, extra water, layers). About 7 days '
            . 'ahead; further out, the stored forecasts for that area and month are summarized and labeled as typical, '
            . 'or it says it can\'t forecast that far.';
    }

    public function parameters(): array
    {
        return self::object([
            'place' => self::object(Geo::placeSchema()),
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default today.'],
            'from_hour' => ['type' => 'integer', 'description' => 'Start of the window, 0-23 local. Default 6.'],
            'to_hour' => ['type' => 'integer', 'description' => 'End of the window, 0-23 local. Default 20.'],
        ], ['place']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'place' => 'required|array',
            'date' => 'nullable|date',
            'from_hour' => 'nullable|integer|min:0|max:23',
            'to_hour' => 'nullable|integer|min:0|max:23',
        ]);
        $place = Geo::resolve($args['place'], $context);
        if (!$place) {
            return ToolResult::unavailable('Could not locate that place.');
        }
        $p = $place['point'];
        $tz = Geo::timezoneFor($p->lat, $p->lng);
        $date = CarbonImmutable::parse($args['date'] ?? $context->today(), $tz)->startOfDay();
        $from = $args['from_hour'] ?? 6;
        $to = max($from, $args['to_hour'] ?? 20);
        $daysOut = $context->now->setTimezone($tz)->startOfDay()->diffInDays($date, false);
        $page = NwsClient::pageFor($p->lat, $p->lng);

        if ($daysOut < 0) {
            return ToolResult::unavailable('That date is in the past. Ask which date they mean.');
        }
        if ($daysOut > self::FORECAST_DAYS) {
            return $this->typical($place, $date, $page);
        }

        $hourly = $this->nws->hourly($p->lat, $p->lng);
        if ($hourly === null) {
            return ToolResult::unavailable('The National Weather Service forecast could not be reached right now.', $page);
        }
        $window = array_values(array_filter($hourly, function($period) use ($date, $from, $to, $tz) {
            $time = CarbonImmutable::parse($period['startTime'])->setTimezone($tz);
            return $time->isSameDay($date) && $time->hour >= $from && $time->hour <= $to;
        }));
        if (!$window) {
            return ToolResult::unavailable('No hourly forecast for that window yet.', $page);
        }

        $temps = array_column($window, 'temperature');
        $rain = array_map(fn($p) => (int) ($p['probabilityOfPrecipitation']['value'] ?? 0), $window);
        $winds = array_map(fn($p) => self::mph($p['windSpeed'] ?? ''), $window);
        $hours = array_map(fn($period) => [
            'time' => CarbonImmutable::parse($period['startTime'])->setTimezone($tz)->format('g A'),
            'temp_f' => $period['temperature'],
            'rain_pct' => (int) ($period['probabilityOfPrecipitation']['value'] ?? 0),
            'wind' => trim(($period['windSpeed'] ?? '') . ' ' . ($period['windDirection'] ?? '')),
            'sky' => $period['shortForecast'] ?? null,
        ], $window);

        $daily = array_values(array_filter($this->nws->daily($p->lat, $p->lng) ?? [], fn($period) => CarbonImmutable::parse($period['startTime'])->setTimezone($tz)->isSameDay($date)));
        $alerts = $this->nws->alerts($p->lat, $p->lng) ?? [];

        $summary = [
            'low_f' => min($temps),
            'high_f' => max($temps),
            'max_rain_pct' => max($rain),
            'rainiest_hour' => $hours[array_search(max($rain), $rain)]['time'],
            'max_wind_mph' => max($winds),
        ];

        return ToolResult::ok([
            'place' => $place['label'],
            'date' => $date->toDateString(),
            'window' => sprintf('%s to %s', date('g A', mktime($from, 0)), date('g A', mktime($to, 0))),
            'summary' => $summary,
            'hourly' => count($hours) > 16 ? array_values(array_filter($hours, fn($h, $i) => $i % 2 === 0, ARRAY_FILTER_USE_BOTH)) : $hours,
            'forecast_text' => array_map(fn($d) => ['period' => $d['name'], 'text' => $d['detailedForecast'] ?? $d['shortForecast']], $daily),
            'alerts' => $alerts,
            'gear_implied' => self::gear($summary, $alerts),
            'note' => 'Forecast for the point\'s weather grid; high trailheads and canyon rims can run several degrees cooler than towns.',
        ], [ToolResult::source('National Weather Service forecast', $page, ToolResult::LIVE, $context->now->format('M j, g:i A'))]);
    }

    private function typical(array $place, CarbonImmutable $date, string $page): ToolResult
    {
        $areaId = $place['area_id'];
        $rows = $areaId ? AreaWeather::where('area_id', $areaId)->whereMonth('start', $date->month)->get() : collect();
        if ($rows->count() < 6) {
            return ToolResult::unavailable(
                'That is more than 7 days out, beyond the forecast, and we have no stored weather for that area and month. Say what\'s typical only if another tool returned it, and suggest checking the forecast closer to the date.',
                $page,
                ['days_out' => $date->diffInDays(now(), true)]
            );
        }
        $days = $rows->filter(fn($r) => (int) date('G', strtotime($r->start)) >= 6 && (int) date('G', strtotime($r->start)) < 18);
        $nights = $rows->diff($days);
        return ToolResult::ok([
            'place' => $place['label'],
            'date' => $date->toDateString(),
            'typical_not_forecast' => true,
            'from_stored_forecasts' => $rows->count() . ' forecast periods saved for ' . $date->format('F') . ' in past years',
            'typical_high_f' => $days->isNotEmpty() ? (int) round($days->avg('temperature')) : null,
            'typical_low_f' => $nights->isNotEmpty() ? (int) round($nights->avg('temperature')) : null,
            'share_of_periods_with_rain_chance_over_30pct' => round($rows->filter(fn($r) => $r->probability_of_precipitation_pt >= 0.3)->count() / $rows->count(), 2),
            'note' => 'Label this as typical for the month, not a forecast; check the forecast within a week of the trip.',
        ], [ToolResult::source('Stored National Weather Service forecasts', $page, ToolResult::OUR_DATA)]);
    }

    private static function mph(string $wind): int
    {
        preg_match_all('/\d+/', $wind, $m);
        return $m[0] ? (int) max($m[0]) : 0;
    }

    private static function gear(array $summary, array $alerts): array
    {
        $gear = [];
        if ($summary['max_rain_pct'] >= 30) {
            $gear[] = "Rain jacket or poncho ({$summary['max_rain_pct']}% chance around {$summary['rainiest_hour']}).";
        }
        if ($summary['max_rain_pct'] >= 30) {
            $gear[] = 'Avoid slot canyons and narrow washes if rain is possible upstream.';
        }
        if ($summary['high_f'] >= 90) {
            $gear[] = "Extra water and electrolytes, sun hat; start early (high of {$summary['high_f']}°F).";
        } elseif ($summary['high_f'] >= 80) {
            $gear[] = 'Plenty of water and sun protection.';
        }
        if ($summary['low_f'] <= 40) {
            $gear[] = "Warm layers, hat and gloves (down to {$summary['low_f']}°F).";
        }
        if ($summary['low_f'] <= 32) {
            $gear[] = 'Traction devices if trails are icy.';
        }
        if ($summary['max_wind_mph'] >= 25) {
            $gear[] = "Windbreaker; take care on exposed edges (gusts to {$summary['max_wind_mph']} mph).";
        }
        foreach ($alerts as $alert) {
            if ($alert['event']) {
                $gear[] = "Active alert: {$alert['event']}.";
            }
        }
        return $gear;
    }
}
