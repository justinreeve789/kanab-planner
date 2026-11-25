<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use Carbon\CarbonImmutable;

class SunTimes extends BaseTool
{
    public const SYNODIC_DAYS = 29.530588853;
    /** A new moon: 2000-01-06 18:14 UTC. */
    public const NEW_MOON_EPOCH = 947182440;

    public function name(): string
    {
        return 'sun_times';
    }

    public function description(): string
    {
        return 'Sunrise, sunset, golden hours, when it gets fully dark, and the moon (phase, how full, next new and '
            . 'full moon) for a place and date, in local time (Arizona has no daylight saving time). Use for sunrise and '
            . 'sunset spots, "when does it get dark", night hikes and stargazing.';
    }

    public function parameters(): array
    {
        return self::object([
            'place' => self::object(Geo::placeSchema()),
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default today.'],
            'days' => ['type' => 'integer', 'description' => 'Consecutive days to return, default 1, at most 10.'],
        ], ['place']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['place' => 'required|array', 'date' => 'nullable|date', 'days' => 'nullable|integer|min:1|max:10']);
        $place = Geo::resolve($args['place'], $context);
        if (!$place) {
            return ToolResult::unavailable('Could not locate that place.');
        }
        $tz = Geo::timezoneFor($place['point']->lat, $place['point']->lng);
        $start = CarbonImmutable::parse($args['date'] ?? $context->today(), $tz)->startOfDay();

        $days = [];
        for ($i = 0; $i < ($args['days'] ?? 1); $i++) {
            $day = $start->addDays($i);
            $days[] = self::day($day, $place['point']->lat, $place['point']->lng, $tz);
        }

        return ToolResult::ok([
            'place' => $place['label'],
            'timezone' => $tz,
            'days' => $days,
            'moon' => self::moon($start),
        ], [ToolResult::source('Sun and moon times (calculated)', null, ToolResult::OUR_DATA)]);
    }

    public static function day(CarbonImmutable $day, float $lat, float $lng, string $tz): array
    {
        $info = date_sun_info($day->setTime(12, 0)->getTimestamp(), $lat, $lng);
        $fmt = fn($ts) => is_int($ts) ? CarbonImmutable::createFromTimestamp($ts, $tz)->format('g:i A') : null;
        return [
            'date' => $day->toDateString(),
            'first_light' => $fmt($info['civil_twilight_begin']),
            'sunrise' => $fmt($info['sunrise']),
            'morning_golden_hour_ends' => is_int($info['sunrise']) ? $fmt($info['sunrise'] + 3600) : null,
            'evening_golden_hour_starts' => is_int($info['sunset']) ? $fmt($info['sunset'] - 3600) : null,
            'sunset' => $fmt($info['sunset']),
            'last_light' => $fmt($info['civil_twilight_end']),
            'fully_dark' => $fmt($info['astronomical_twilight_end']),
            'daylight_hours' => is_int($info['sunrise']) && is_int($info['sunset']) ? round(($info['sunset'] - $info['sunrise']) / 3600, 1) : null,
        ];
    }

    /** Moon phase from the mean synodic month (good to within about a day). */
    public static function moon(CarbonImmutable $date): array
    {
        $age = fmod(($date->setTime(21, 0)->getTimestamp() - self::NEW_MOON_EPOCH) / 86400, self::SYNODIC_DAYS);
        $age = $age < 0 ? $age + self::SYNODIC_DAYS : $age;
        $fraction = $age / self::SYNODIC_DAYS;
        $illumination = (1 - cos(2 * M_PI * $fraction)) / 2;
        $name = match (true) {
            $age < 1.5 || $age > self::SYNODIC_DAYS - 1.5 => 'new moon',
            $age < 6.4 => 'waxing crescent',
            $age < 8.9 => 'first quarter',
            $age < 13.8 => 'waxing gibbous',
            $age < 15.8 => 'full moon',
            $age < 20.6 => 'waning gibbous',
            $age < 23.1 => 'last quarter',
            default => 'waning crescent',
        };
        $toNew = self::SYNODIC_DAYS - $age;
        $toFull = $age <= self::SYNODIC_DAYS / 2 ? self::SYNODIC_DAYS / 2 - $age : self::SYNODIC_DAYS * 1.5 - $age;
        return [
            'phase' => $name,
            'percent_lit' => (int) round($illumination * 100),
            'next_new_moon' => $date->addDays((int) round($toNew))->toDateString(),
            'next_full_moon' => $date->addDays((int) round($toFull))->toDateString(),
            'stargazing' => $illumination < 0.25 ? 'dark skies (little moonlight)' : ($illumination < 0.6 ? 'some moonlight' : 'bright moon washes out faint stars'),
            'note' => 'Moon dates are approximate (within about a day).',
        ];
    }
}
