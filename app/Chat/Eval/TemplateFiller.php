<?php

namespace App\Chat\Eval;

use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Turns a question template ("Can a {age}-year-old hike {trail}?") into a real question using records from the
 * database and the vocabularies in config chat.eval.values. Seeded, so a run can be repeated exactly.
 */
final class TemplateFiller
{
    private array $pools = [];

    public function __construct(private int $seed, private CarbonImmutable $today)
    {
        mt_srand($seed);
    }

    /** @return array{question: string, setup: string[], values: array} */
    public function fill(array $template): array
    {
        $used = [];
        $values = [];
        $replace = function(string $text) use (&$used, &$values) {
            return preg_replace_callback('/\{(\w+)\}/', function($m) use (&$used, &$values) {
                $key = $m[1];
                // The same placeholder twice in one question gets two different values ({area} and {area}).
                $value = $this->pick($key, $used[$key] ?? []);
                $used[$key][] = $value;
                $values[$key][] = $value;
                return $value;
            }, $text);
        };

        // Setup turns and the question share values: "Tell me about {trail}" then "Is it dog-friendly?".
        $setup = [];
        foreach ($template['setup'] ?? [] as $turn) {
            $setup[] = $replace($turn);
            $used = array_map(fn($v) => [], $used); // allow the same trail to be named again
        }
        $question = $template['ask'] ?? $template['template'];
        if ($setup && isset($values['trail'])) {
            // Follow-ups refer back to the setup's record.
            $question = str_replace('{trail}', $values['trail'][0], $question);
        }
        return ['question' => $replace($question), 'setup' => $setup, 'values' => $values];
    }

    private function pick(string $key, array $exclude): string
    {
        $pool = array_values(array_diff($this->pool($key), $exclude)) ?: $this->pool($key);
        return (string) ($pool ? $pool[mt_rand(0, count($pool) - 1)] : "{{$key}}");
    }

    private function pool(string $key): array
    {
        return $this->pools[$key] ??= match ($key) {
            // Trails with a GPS profile, so steepness questions have something to find.
            'trail' => DB::table('trails')->join('trail_profiles', 'trail_profiles.trail_id', '=', 'trails.id')
                ->where('trails.is_usable', 1)->whereRaw('CHAR_LENGTH(trails.name) BETWEEN 6 AND 40')
                ->orderBy('trails.id')->pluck('trails.name')
                ->map(fn($n) => trim(preg_replace('/\s*#\s*\d+$/', '', html_entity_decode($n, ENT_QUOTES))))->unique()->values()->all(),
            'restaurant' => DB::table('restaurants')->where('is_usable', 1)->orderBy('id')->pluck('name')->all(),
            'lodging' => DB::table('locations')->where('is_usable', 1)
                ->whereExists(fn($q) => $q->from('location_tags')->whereColumn('location_tags.location_id', 'locations.id')->where('tag', 'lodging'))
                ->orderBy('id')->limit(60)->pluck('name')->all(),
            'area' => Area::orderBy('id')->pluck('name')->all(),
            'location' => ['Kanab', 'Springdale', 'Page', 'St. George', 'Bryce Canyon City', 'Hurricane', 'Zion National Park', 'Big Water'],
            'date' => array_map(fn($d) => $this->today->addDays($d)->format('l, F j'), range(1, 6)),
            'day' => ['Sunday', 'Monday', 'Friday', 'Saturday'],
            'month' => ['March', 'May', 'July', 'September', 'October', 'December'],
            'time' => ['6 AM', '8 AM', '2 PM', '5 PM', '9 PM'],
            default => config("chat.eval.values.{$key}", []),
        };
    }
}
