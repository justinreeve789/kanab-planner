<?php

namespace App\Chat\Data;

use App\Models\Trail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finds trails by name (tolerating typos and partial names) and filters, and folds duplicate records of the same
 * trail into one result.
 */
final class TrailFinder
{
    public const STRONG_MATCH = 0.85;

    /**
     * @param array $filters area_id, max_miles, min_miles, max_gain_ft, difficulty, activity, feature, free_only,
     *                       dogs_allowed, near (Point), within_miles, exclude_ids
     * @return array{matches: array, ambiguous: bool, duplicates_folded: int}
     */
    public function search(?string $query, array $filters = [], int $limit = 8): array
    {
        $rows = $this->candidates($filters);
        $query = trim((string) $query);

        if ($query !== '') {
            $phrase = self::normalize($query);
            // Words that appear in only a few trail names are distinctive: "Kanarra" alone should find Kanarra Creek.
            $frequency = array_count_values(array_merge(...$rows->map(fn($r) => array_unique(explode(' ', self::core($r->name))))->all() ?: [[]]));
            $rare = array_filter(explode(' ', self::core($query)), fn($w) => strlen($w) >= 5 && ($frequency[$w] ?? 99) <= 3);
            $rows = $rows->map(function($row) use ($query, $phrase, $rare) {
                $row->match_score = max(self::score($query, $row->name), self::realAlias($row->name_alt) ? self::score($query, $row->name_alt) * 0.95 : 0);
                // "Kanarra Falls" finds "Kanarra Creek Trail" when its description names the falls.
                if ($row->match_score < 0.6 && mb_strlen($phrase) >= 6 && preg_match('/\b' . preg_quote($phrase, '/') . '\b/', self::normalize((string) $row->description))) {
                    $row->match_score = max($row->match_score, 0.6);
                }
                if ($row->match_score < 0.55 && $rare && array_intersect($rare, explode(' ', self::core($row->name)))) {
                    $row->match_score = 0.55;
                }
                return $row;
            })->filter(fn($row) => $row->match_score >= 0.45)->sortByDesc('match_score');
        } else {
            $rows = $rows->sortBy([fn($a, $b) => ($b->has_profile <=> $a->has_profile), fn($a, $b) => (($a->distance_miles ?? 99) <=> ($b->distance_miles ?? 99))]);
            if (isset($filters['near'])) {
                $rows = $rows->sortBy('miles_away');
            }
        }

        [$rows, $folded] = $this->fold($rows->values());
        $matches = $rows->take($limit)->map(fn($row) => $this->present($row))->values()->all();

        // Ambiguous: several strong name matches that are different trails (not duplicates).
        // Ambiguous: no single exact match, and several different trails either match strongly or contain every
        // word typed ("Overlook" → Canyon Overlook, Timber Creek Overlook, Horseshoe Bend Overlook...).
        $strong = array_filter($matches, fn($m) => ($m['match_score'] ?? 0) >= self::STRONG_MATCH);
        $exact = array_filter($matches, fn($m) => self::core($m['name']) === self::core($query));
        $words = array_filter(explode(' ', self::core($query)));
        $containing = array_filter($matches, fn($m) => $words && !array_diff($words, explode(' ', self::core($m['name']))));
        $ambiguous = $query !== '' && count($exact) !== 1 && (count($strong) > 1 || count($containing) > 1);

        return ['matches' => $matches, 'ambiguous' => $ambiguous, 'duplicates_folded' => $folded];
    }

    private function candidates(array $filters): Collection
    {
        $q = DB::table('trails')
            ->leftJoin('areas', 'areas.id', '=', 'trails.area_id')
            ->leftJoin('trail_profiles', 'trail_profiles.trail_id', '=', 'trails.id')
            ->leftJoinSub(
                DB::table('resource_data')->where('resource_table', 'trails')->groupBy('resource_id')->select([
                    'resource_id', DB::raw('MAX(adventurousness) as adventurousness'),
                    DB::raw('MAX(solitariness) as solitariness'), DB::raw('MAX(strenuousness) as strenuousness'),
                ]),
                'resource_data', 'resource_data.resource_id', '=', 'trails.id'
            )
            ->where('trails.is_usable', 1)
            ->select([
                'trails.id', 'trails.name', 'trails.name_alt', 'trails.area_id', 'areas.name as area', 'trails.distance_miles',
                'trails.distance_type', 'trails.incline_ft', 'trails.difficulty', 'trails.average_hours', 'trails.latitude',
                'trails.longitude', 'trails.fee', 'trails.reservation', 'trails.pets_allowed', 'trails.features', 'trails.unique', 'trails.description',
                'trail_profiles.steepest_sustained_pct', 'trail_profiles.gain_ft as profile_gain_ft',
                DB::raw('trail_profiles.trail_id IS NOT NULL as has_profile'),
                'resource_data.adventurousness', 'resource_data.solitariness', 'resource_data.strenuousness',
            ]);

        if (!empty($filters['area_id'])) {
            $q->where('trails.area_id', $filters['area_id']);
        }
        if (isset($filters['max_miles'])) {
            $q->where('trails.distance_miles', '<=', $filters['max_miles']);
        }
        if (isset($filters['min_miles'])) {
            $q->where('trails.distance_miles', '>=', $filters['min_miles']);
        }
        if (isset($filters['max_gain_ft'])) {
            $q->where(fn($w) => $w->where('trails.incline_ft', '<=', $filters['max_gain_ft'])->orWhere('trail_profiles.gain_ft', '<=', $filters['max_gain_ft']));
        }
        if (!empty($filters['difficulty'])) {
            $q->where('trails.difficulty', 'like', '%' . $filters['difficulty'] . '%');
        }
        if (!empty($filters['free_only'])) {
            $q->where(fn($w) => $w->whereNull('trails.fee')->orWhere('trails.fee', 0));
        }
        if (!empty($filters['dogs_allowed'])) {
            $q->where('trails.pets_allowed', 'like', 'yes%');
        }
        if (!empty($filters['feature'])) {
            $feature = $filters['feature'];
            $q->where(fn($w) => $w->where('trails.features', 'like', "%{$feature}%")
                ->orWhere('trails.description', 'like', "%{$feature}%")
                ->orWhereExists(fn($t) => $t->from('trail_tags')->whereColumn('trail_tags.trail_id', 'trails.id')->where('tag', 'like', "%{$feature}%")));
        }
        if (!empty($filters['activity'])) {
            $q->whereExists(fn($a) => $a->from('resource_activity_types')
                ->join('activity_types', 'activity_types.id', '=', 'resource_activity_types.activity_type_id')
                ->whereColumn('resource_activity_types.resource_id', 'trails.id')
                ->where('resource_activity_types.resource_table', 'trails')
                ->where('activity_types.name', 'like', '%' . $filters['activity'] . '%'));
        }
        if (!empty($filters['exclude_ids'])) {
            $q->whereNotIn('trails.id', $filters['exclude_ids']);
        }

        $rows = $q->get();
        if (isset($filters['near'])) {
            $near = $filters['near'];
            $within = $filters['within_miles'] ?? 40;
            $rows = $rows->map(function($row) use ($near) {
                $row->miles_away = ($row->latitude && $row->longitude)
                    ? round(TrailProfiler::miles($near->lat, $near->lng, (float) $row->latitude, (float) $row->longitude), 1)
                    : null;
                return $row;
            })->filter(fn($row) => $row->miles_away !== null && $row->miles_away <= $within);
        }
        return $rows;
    }

    /** Fold records that are the same trail: same normalized name within a mile, or merged records. */
    private function fold(Collection $rows): array
    {
        $kept = [];
        $folded = 0;
        foreach ($rows as $row) {
            foreach ($kept as $i => $existing) {
                if (self::sameTrail($existing, $row)) {
                    $kept[$i]->duplicate_ids[] = $row->id;
                    // Prefer the record with a GPS profile.
                    if (!$existing->has_profile && $row->has_profile) {
                        $row->duplicate_ids = [...$existing->duplicate_ids, $existing->id];
                        $row->duplicate_ids = array_values(array_diff($row->duplicate_ids, [$row->id]));
                        $kept[$i] = $row;
                    }
                    $folded++;
                    continue 2;
                }
            }
            $row->duplicate_ids = [];
            $row->related_ids = [];
            foreach ($kept as $i => $existing) {
                if ($existing->area_id === $row->area_id && self::normalize(self::core($existing->name)) === self::normalize(self::core($row->name))) {
                    $kept[$i]->related_ids[] = $row->id;
                    $row->related_ids[] = $existing->id;
                }
            }
            $kept[] = $row;
        }
        return [collect($kept), $folded];
    }

    private static function sameTrail(object $a, object $b): bool
    {
        if (self::normalize(self::core($a->name)) !== self::normalize(self::core($b->name)) || $a->area_id !== $b->area_id) {
            return false;
        }
        if (!$a->latitude || !$b->latitude) {
            return true;
        }
        return TrailProfiler::miles((float) $a->latitude, (float) $a->longitude, (float) $b->latitude, (float) $b->longitude) <= 1.0;
    }

    private function present(object $row): array
    {
        return array_filter([
            'trail_id' => $row->id,
            'name' => html_entity_decode($row->name, ENT_QUOTES),
            'area_id' => $row->area_id,
            'area' => $row->area,
            'distance_miles' => $row->distance_miles !== null ? (float) $row->distance_miles : null,
            'route_type' => $row->distance_type,
            'climb_ft' => $row->incline_ft ?? $row->profile_gain_ft,
            'difficulty' => $row->difficulty,
            'steepest_tenth_mile_pct' => $row->steepest_sustained_pct !== null ? (float) $row->steepest_sustained_pct : null,
            'has_gps_profile' => (bool) $row->has_profile,
            'fee' => $row->fee === null ? null : (bool) $row->fee,
            'permit_or_reservation' => $row->reservation === null ? null : (bool) $row->reservation,
            'scores' => $row->adventurousness !== null ? [
                'adventurous' => round((float) $row->adventurousness, 2),
                'solitude' => round((float) $row->solitariness, 2),
                'strenuous' => round((float) $row->strenuousness, 2),
            ] : null,
            'miles_away' => $row->miles_away ?? null,
            'match_score' => isset($row->match_score) ? round($row->match_score, 2) : null,
            'duplicate_record_ids' => $row->duplicate_ids ?: null,
            // Same name and area but far apart: usually a segment of the hike and the whole hike. Use both.
            'same_name_records' => ($row->related_ids ?? []) ?: null,
        ], fn($v) => $v !== null);
    }

    /** name_alt often holds an id or area number rather than a name. */
    private static function realAlias(?string $alias): bool
    {
        return $alias !== null && preg_match('/[a-z]{3}/i', $alias) === 1;
    }

    /** Edit distance where swapping two neighboring letters ("angles" for "angels") counts as one typo. */
    public static function typoDistance(string $a, string $b): int
    {
        $la = strlen($a);
        $lb = strlen($b);
        $d = [];
        for ($i = 0; $i <= $la; $i++) {
            $d[$i] = [$i];
        }
        for ($j = 0; $j <= $lb; $j++) {
            $d[0][$j] = $j;
        }
        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cost);
                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }
        return $d[$la][$lb];
    }

    public static function normalize(string $name): string
    {
        $name = mb_strtolower(html_entity_decode($name, ENT_QUOTES));
        $name = preg_replace('/#\s*\d+/', '', $name);
        $name = preg_replace("/['’]/u", '', $name);
        $name = preg_replace('/[^a-z0-9 ]+/u', ' ', $name);
        $name = preg_replace('/\b(the|a)\b/', ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /** The distinctive part of a name: "Take a stroll to Horseshoe Bend Overlook" → "horseshoe bend overlook". */
    private static function core(string $name): string
    {
        $n = self::normalize($name);
        $n = preg_replace('/^(take a (stroll|hike|walk) to|hike to|walk to)\s+/', '', $n);
        return trim(preg_replace('/\s+(trail|trailhead|hike|route)$/', '', $n));
    }

    /**
     * 0..1 similarity between what was typed and a trail name: exact, contained, all words present, then
     * typo-tolerant word matching.
     */
    public static function score(string $query, string $name): float
    {
        $q = self::core($query);
        $n = self::core($name);
        if ($q === '' || $n === '') {
            return 0.0;
        }
        if ($q === $n) {
            return 1.0;
        }
        if (preg_match('/\b' . preg_quote($q, '/') . '\b/', $n)) {
            return 0.9 * (0.6 + 0.4 * strlen($q) / strlen($n));
        }
        if (strlen($n) >= 4 && preg_match('/\b' . preg_quote($n, '/') . '\b/', $q)) {
            return 0.88;
        }
        $qWords = explode(' ', $q);
        $nWords = explode(' ', $n);
        $hits = 0.0;
        foreach ($qWords as $word) {
            $best = 0.0;
            foreach ($nWords as $candidate) {
                if ($word === $candidate) {
                    $best = 1.0;
                    break;
                }
                $len = max(strlen($word), strlen($candidate));
                if ($len >= 4) {
                    $best = max($best, 1 - self::typoDistance($word, $candidate) / $len);
                }
            }
            $hits += $best >= 0.75 ? $best : 0;
        }
        $coverage = $hits / count($qWords);
        $precision = min(1.0, $hits / count($nWords));
        return round(0.8 * (0.7 * $coverage + 0.3 * $precision), 3);
    }
}
