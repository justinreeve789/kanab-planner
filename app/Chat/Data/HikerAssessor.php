<?php

namespace App\Chat\Data;

/**
 * Compares a trail against limits for a kind of hiker (config chat.hikers) and returns a verdict with the evidence
 * behind it. The model words the answer; the judgment is made here so it's consistent and testable.
 */
final class HikerAssessor
{
    public const GOOD = 'good';
    public const CAVEATS = 'doable_with_caveats';
    public const NO = 'not_recommended';

    public function __construct(private array $bands)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('chat.hikers'));
    }

    public function bandFor(?int $age, ?string $fitness): string
    {
        if ($fitness && isset($this->bands[$fitness]) && ($age === null || $age >= 18)) {
            return $fitness;
        }
        return match (true) {
            $age === null => $fitness && isset($this->bands[$fitness]) ? $fitness : 'moderate',
            $age < 4 => 'carried',
            $age <= 6 => 'age_4_6',
            $age <= 9 => 'age_7_9',
            $age <= 12 => 'age_10_12',
            $age <= 17 => 'teen',
            $age >= 65 => 'beginner',
            default => 'moderate',
        };
    }

    /**
     * @param array $trip from TripShape::for(): miles, gain_ft, hours, shape notes
     * @param array|null $profile TrailProfiler output (null when there's no GPS track)
     * @param array $flags ['exposure' => [claims], 'scrambling' => [...], ...] from blog facts
     */
    public function assess(string $bandKey, array $trip, ?array $profile, array $flags = []): array
    {
        $band = $this->bands[$bandKey];
        $issues = [];
        $missing = [];

        $check = function(string $what, ?float $value, float $limit, string $unit, string $note) use (&$issues) {
            if ($value === null) {
                return;
            }
            if ($value > $limit) {
                $issues[] = [
                    'check' => $what,
                    'value' => round($value, 1),
                    'limit' => $limit,
                    'unit' => $unit,
                    'severity' => $value > $limit * 1.25 ? 'major' : 'minor',
                    'note' => $note,
                ];
            }
        };

        $check('distance', $trip['miles'], $band['max_miles'], 'miles', $trip['distance_note'] ?? 'Total distance for the hike as planned.');
        $check('total_climb', $trip['gain_ft'], $band['max_gain_ft'], 'feet', 'Total elevation gain for the hike as planned.');
        $check('time', $trip['hours'], $band['max_hours'], 'hours', 'Estimated hiking time at this hiker\'s pace.');

        $turnaround = null;
        if ($profile) {
            $sustained = $profile['steepest_sustained'];
            $pitch = $profile['steepest_pitch'];
            if ($sustained) {
                $check('steepest_tenth_mile', $sustained['grade_pct'], $band['max_sustained_pct'], '% grade',
                    "Steepest 0.1 mile, going {$sustained['direction']}, starting at mile {$sustained['starts_at_mile']}.");
            }
            if ($pitch) {
                $check('steepest_pitch', $pitch['grade_pct'], $band['max_pitch_pct'], '% grade',
                    "Steepest short pitch (0.05 mile), going {$pitch['direction']}, at mile {$pitch['starts_at_mile']}.");
            }
            $turnaround = $this->turnaround($profile, $band);
        } else {
            $missing[] = 'No GPS track for this trail, so the steepest sections are unknown; judge from total climb and blog reports.';
        }

        foreach (['exposure', 'scrambling', 'chains', 'drop_offs'] as $flag) {
            if (!empty($flags[$flag]) && !$band['exposure_ok']) {
                $issues[] = [
                    'check' => $flag,
                    'value' => count($flags[$flag]),
                    'limit' => 0,
                    'unit' => 'reports',
                    'severity' => 'major',
                    'note' => 'Reported in travel blogs or reviews: ' . implode(' | ', array_slice($flags[$flag], 0, 2)),
                ];
            }
        }

        $majors = array_filter($issues, fn($i) => $i['severity'] === 'major');
        $verdict = match (true) {
            count($majors) > 0 && !$turnaround => self::NO,
            count($majors) > 0 && $turnaround => self::CAVEATS,
            count($issues) > 0 => self::CAVEATS,
            default => self::GOOD,
        };
        // A short, partial hike is only a fallback when the problem is a section, not the whole route.
        if ($verdict === self::CAVEATS && $turnaround && count(array_filter($majors, fn($i) => in_array($i['check'], ['exposure', 'scrambling', 'chains', 'drop_offs'], true))) > 0) {
            $verdict = self::NO;
        }

        return [
            'hiker' => $band['label'],
            'verdict' => $verdict,
            'issues' => array_values($issues),
            'turnaround' => $turnaround,
            'limits_used' => array_intersect_key($band, array_flip(['max_miles', 'max_gain_ft', 'max_sustained_pct', 'max_pitch_pct', 'max_hours'])),
            'confidence' => $profile ? 'high' : ($trip['miles'] !== null ? 'medium' : 'low'),
            'missing' => $missing,
            'note' => 'Limits are general guidance for this kind of hiker, not a guarantee; heat, footing and the hiker\'s own experience matter too.',
        ];
    }

    /**
     * Where to turn back to skip the first section steeper than this hiker's limit, if that leaves a worthwhile walk
     * (at least 0.25 mile in).
     */
    private function turnaround(array $profile, array $band): ?array
    {
        $limit = $band['max_sustained_pct'];
        $sections = array_merge($profile['steep_sections'], $profile['very_steep_sections']);
        usort($sections, fn($a, $b) => $a['from_mile'] <=> $b['from_mile']);
        foreach ($sections as $section) {
            if ($section['max_grade_pct'] > $band['max_pitch_pct'] || $section['average_grade_pct'] > $limit) {
                if ($section['from_mile'] >= 0.25) {
                    return [
                        'turn_around_at_mile' => $section['from_mile'],
                        'round_trip_miles' => round($section['from_mile'] * 2, 1),
                        'reason' => "Before the {$section['direction']}hill section at miles {$section['from_mile']}–{$section['to_mile']} (about {$section['average_grade_pct']}% average, up to {$section['max_grade_pct']}%).",
                    ];
                }
                return null;
            }
        }
        return null;
    }
}
