<?php

namespace App\Chat\Data;

/**
 * Turns a trail's GPS track into the numbers a "can my kid do this?" answer needs: total climb, how steep it gets and
 * where, which stretches are steep, and how much of it is gentle.
 *
 * Tracks are resampled every STEP_MI along their length and the elevation smoothed, so GPS jitter doesn't show up as
 * fake climbs or spikes. Grades are rise over run in percent, measured over a window (0.05, 0.1, 0.25 mi): a short
 * window finds pitches, a longer one finds sustained climbs.
 */
final class TrailProfiler
{
    public const STEP_MI = 0.01;
    public const SMOOTH_SAMPLES = 1;        // moving average of 1 sample either side (~0.03 mi)
    public const GAIN_HYSTERESIS_FT = 3;    // ignore wiggles smaller than this when totalling climb
    // (These two were tuned against listed climbs: computed totals land a median 4% under them.)
    public const FT_PER_MI = 5280;
    public const FT_PER_M = 3.28084;
    public const MAX_PROFILE_POINTS = 120;

    public function __construct(
        private float $steepPct = 15,
        private float $verySteepPct = 25,
        private float $gentlePct = 8,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self((float) config('chat.steep_pct', 15), (float) config('chat.very_steep_pct', 25), (float) config('chat.gentle_pct', 8));
    }

    /**
     * @param string|array $geojson FeatureCollection/Feature/geometry with [lng, lat, elevation_m] coordinates
     * @return array|null null when the track has fewer than two points with elevation
     */
    public function profile(string|array $geojson): ?array
    {
        $points = self::points(is_string($geojson) ? json_decode($geojson, true) : $geojson);
        if (count($points) < 2) {
            return null;
        }

        [$distances, $elevations] = self::alongTrack($points);
        $lengthMi = end($distances);
        if ($lengthMi < 0.05) {
            return null;
        }
        [$d, $e] = self::resample($distances, $elevations, self::STEP_MI);
        $e = self::smooth($e, self::SMOOTH_SAMPLES);

        [$gain, $loss] = self::gainLoss($e, self::GAIN_HYSTERESIS_FT);
        $pitch = $this->steepest($d, $e, 0.05);
        $sustained = $this->steepest($d, $e, 0.1);
        $long = $this->steepest($d, $e, 0.25);
        $grades = $this->windowGrades($d, $e, 0.05);

        $gentle = $grades ? count(array_filter($grades, fn($g) => abs($g) < $this->gentlePct)) / count($grades) : 1.0;
        $endGap = self::miles($points[0][0], $points[0][1], end($points)[0], end($points)[1]);

        return [
            'track_miles' => round($lengthMi, 2),
            // The track comes back to where it started (a loop, or an out-and-back drawn both ways).
            'returns_to_start' => $endGap <= max(0.1, 0.1 * $lengthMi),
            'gain_ft' => (int) round($gain),
            'loss_ft' => (int) round($loss),
            'min_elevation_ft' => (int) round(min($e)),
            'max_elevation_ft' => (int) round(max($e)),
            'start_elevation_ft' => (int) round($e[0]),
            'end_elevation_ft' => (int) round(end($e)),
            'steepest_pitch' => $pitch,          // 0.05 mi
            'steepest_sustained' => $sustained,  // 0.1 mi
            'steepest_quarter_mile' => $long,    // 0.25 mi
            'gentle_share' => round($gentle, 2),
            'steep_sections' => $this->sections($d, $grades, $e, $this->steepPct),
            'very_steep_sections' => $this->sections($d, $grades, $e, $this->verySteepPct),
            'longest_climb' => self::longestClimb($d, $e, 25),
            'profile' => self::downsample($d, $e, self::MAX_PROFILE_POINTS),
        ];
    }

    /** [[lat, lng, elevation_ft], ...] in track order. Points without elevation are dropped. */
    public static function points(?array $geojson): array
    {
        if (!$geojson) {
            return [];
        }
        $lines = [];
        $collect = function(?array $geometry) use (&$lines, &$collect) {
            if (!$geometry) {
                return;
            }
            match ($geometry['type'] ?? null) {
                'LineString' => $lines[] = $geometry['coordinates'] ?? [],
                'MultiLineString' => array_push($lines, ...($geometry['coordinates'] ?? [])),
                'GeometryCollection' => array_map($collect, $geometry['geometries'] ?? []),
                default => null,
            };
        };
        match ($geojson['type'] ?? null) {
            'FeatureCollection' => array_map(fn($feature) => $collect($feature['geometry'] ?? null), $geojson['features'] ?? []),
            'Feature' => $collect($geojson['geometry'] ?? null),
            default => $collect($geojson),
        };

        $points = [];
        foreach ($lines as $line) {
            foreach ($line as $c) {
                if (isset($c[0], $c[1], $c[2]) && is_numeric($c[2])) {
                    $points[] = [(float) $c[1], (float) $c[0], (float) $c[2] * self::FT_PER_M];
                }
            }
        }
        return $points;
    }

    /** Cumulative miles and elevations, skipping duplicate points. */
    private static function alongTrack(array $points): array
    {
        $distances = [0.0];
        $elevations = [$points[0][2]];
        $total = 0.0;
        for ($i = 1, $n = count($points); $i < $n; $i++) {
            $step = self::miles($points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]);
            if ($step <= 0.0) {
                continue;
            }
            // A jump of more than 2 miles between consecutive points is a gap between track pieces, not trail.
            $total += min($step, 2.0);
            $distances[] = $total;
            $elevations[] = $points[$i][2];
        }
        return [$distances, $elevations];
    }

    public static function miles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 3958.8 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private static function resample(array $distances, array $elevations, float $step): array
    {
        $length = end($distances);
        $d = [];
        $e = [];
        $j = 0;
        $last = count($distances) - 1;
        for ($x = 0.0; $x <= $length + 1e-9; $x += $step) {
            while ($j < $last - 1 && $distances[$j + 1] < $x) {
                $j++;
            }
            $span = $distances[$j + 1] - $distances[$j];
            $t = $span > 0 ? max(0.0, min(1.0, ($x - $distances[$j]) / $span)) : 0.0;
            $d[] = round($x, 4);
            $e[] = $elevations[$j] + $t * ($elevations[$j + 1] - $elevations[$j]);
        }
        return [$d, $e];
    }

    private static function smooth(array $values, int $half): array
    {
        $n = count($values);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $from = max(0, $i - $half);
            $to = min($n - 1, $i + $half);
            $out[] = array_sum(array_slice($values, $from, $to - $from + 1)) / ($to - $from + 1);
        }
        return $out;
    }

    private static function gainLoss(array $e, float $hysteresis): array
    {
        $gain = 0.0;
        $loss = 0.0;
        $anchor = $e[0];
        foreach ($e as $value) {
            $delta = $value - $anchor;
            if ($delta >= $hysteresis) {
                $gain += $delta;
                $anchor = $value;
            } elseif ($delta <= -$hysteresis) {
                $loss -= $delta;
                $anchor = $value;
            }
        }
        return [$gain, $loss];
    }

    /** Grade (%) of each window of $windowMi starting at each sample. */
    private function windowGrades(array $d, array $e, float $windowMi): array
    {
        $span = (int) round($windowMi / self::STEP_MI);
        $grades = [];
        for ($i = 0, $n = count($d); $i + $span < $n; $i++) {
            $run = ($d[$i + $span] - $d[$i]) * self::FT_PER_MI;
            $grades[] = $run > 0 ? ($e[$i + $span] - $e[$i]) / $run * 100 : 0.0;
        }
        return $grades;
    }

    /** The steepest window of $windowMi, up or down: grade, where it starts and which way. */
    private function steepest(array $d, array $e, float $windowMi): ?array
    {
        $grades = $this->windowGrades($d, $e, $windowMi);
        if (!$grades) {
            return null;
        }
        $best = 0;
        foreach ($grades as $i => $grade) {
            if (abs($grade) > abs($grades[$best])) {
                $best = $i;
            }
        }
        return [
            'grade_pct' => round(abs($grades[$best]), 1),
            'direction' => $grades[$best] >= 0 ? 'up' : 'down',
            'starts_at_mile' => round($d[$best], 2),
            'length_miles' => $windowMi,
        ];
    }

    /**
     * Stretches where the 0.05-mile grade stays at or above $threshold (either direction), merged when less than 0.03
     * mi apart, and kept when at least 0.03 mi long.
     */
    private function sections(array $d, array $grades, array $e, float $threshold): array
    {
        $span = (int) round(0.05 / self::STEP_MI);
        $runs = [];
        $start = null;
        foreach ($grades as $i => $grade) {
            if (abs($grade) >= $threshold) {
                $start ??= $i;
            } elseif ($start !== null) {
                $runs[] = [$start, $i - 1];
                $start = null;
            }
        }
        if ($start !== null) {
            $runs[] = [$start, count($grades) - 1];
        }

        // Merge runs separated by short gaps.
        $merged = [];
        foreach ($runs as $run) {
            $last = array_key_last($merged);
            if ($last !== null && ($run[0] - $merged[$last][1]) * self::STEP_MI < 0.03) {
                $merged[$last][1] = $run[1];
            } else {
                $merged[] = $run;
            }
        }

        $sections = [];
        foreach ($merged as [$from, $to]) {
            $endIndex = min($to + $span, count($d) - 1);
            $startMi = $d[$from];
            $endMi = $d[$endIndex];
            $length = $endMi - $startMi;
            if ($length < 0.03) {
                continue;
            }
            $change = $e[$endIndex] - $e[$from];
            $slice = array_slice($grades, $from, $to - $from + 1);
            $max = array_reduce($slice, fn($carry, $g) => abs($g) > abs($carry) ? $g : $carry, 0.0);
            $sections[] = [
                'from_mile' => round($startMi, 2),
                'to_mile' => round($endMi, 2),
                'length_miles' => round($length, 2),
                'direction' => $change >= 0 ? 'up' : 'down',
                'elevation_change_ft' => (int) round(abs($change)),
                'average_grade_pct' => round(abs($change) / ($length * self::FT_PER_MI) * 100, 1),
                'max_grade_pct' => round(abs($max), 1),
            ];
        }
        return $sections;
    }

    /** The biggest climb with no descent larger than $toleranceFt along the way. */
    private static function longestClimb(array $d, array $e, float $toleranceFt): ?array
    {
        $best = null;
        $start = 0;
        $peak = 0;
        for ($i = 1, $n = count($e); $i < $n; $i++) {
            if ($e[$i] > $e[$peak]) {
                $peak = $i;
            }
            if ($e[$peak] - $e[$i] > $toleranceFt || $i === $n - 1) {
                $gain = $e[$peak] - $e[$start];
                if ($gain > 0 && (!$best || $gain > $best['gain_ft'])) {
                    $length = $d[$peak] - $d[$start];
                    $best = [
                        'from_mile' => round($d[$start], 2),
                        'to_mile' => round($d[$peak], 2),
                        'gain_ft' => (int) round($gain),
                        'average_grade_pct' => $length > 0 ? round($gain / ($length * self::FT_PER_MI) * 100, 1) : 0.0,
                    ];
                }
                $start = $i;
                $peak = $i;
            } elseif ($e[$i] < $e[$start]) {
                $start = $i;
                $peak = $i;
            }
        }
        return $best && $best['gain_ft'] >= 20 ? $best : null;
    }

    private static function downsample(array $d, array $e, int $max): array
    {
        $n = count($d);
        $every = max(1, (int) ceil($n / $max));
        $out = [];
        for ($i = 0; $i < $n; $i += $every) {
            $out[] = [round($d[$i], 2), (int) round($e[$i])];
        }
        if (end($out)[0] !== round(end($d), 2)) {
            $out[] = [round(end($d), 2), (int) round(end($e))];
        }
        return $out;
    }
}
