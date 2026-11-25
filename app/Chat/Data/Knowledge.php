<?php

namespace App\Chat\Data;

use App\Models\Area;
use App\Models\ResourceFact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What travel blogs and reviews say about a trail, restaurant or place, by topic.
 *
 * Three layers, best first: extracted facts (resource_facts, from `chat:extract-facts`), then sentences from blog
 * posts linked to the record or naming it (in a post that also names its area), then reviews. Everything returned
 * is short excerpts with the post, blog, link and date, so answers can cite and weigh recency.
 */
final class Knowledge
{
    public const TOPICS = [
        'gear' => ['bring', 'pack', 'wear', 'shoes', 'boots', 'poles', 'sandals', 'helmet', 'headlamp', 'gloves', 'wetsuit', 'dry suit', 'drysuit', 'neoprene', 'sunscreen', 'layers', 'microspikes', 'crampons', 'traction', 'water bottle', 'liters'],
        'hazards' => ['danger', 'careful', 'caution', 'flash flood', 'slippery', 'loose', 'cliff', 'drop-off', 'drop off', 'exposed', 'rattlesnake', 'scorpion', 'heat', 'lightning', 'rockfall', 'rescue', 'injur'],
        'exposure' => ['exposed', 'exposure', 'drop-off', 'drop off', 'dropoff', 'cliff', 'ledge', 'sheer', 'heights', 'vertigo', 'chains'],
        'scrambling' => ['scramble', 'scrambling', 'hands and feet', 'boulder', 'ladder', 'rope', 'chains', 'climb up'],
        'kids' => ['kid', 'child', 'family', 'families', 'toddler', 'stroller', 'little ones', 'son', 'daughter', 'year-old', 'year old'],
        'dogs' => ['dog', 'pet', 'leash'],
        'water' => ['water source', 'spring', 'creek', 'stream', 'river', 'pool', 'refill', 'filter', 'water is'],
        'shade' => ['shade', 'shady', 'no shade', 'full sun', 'exposed to the sun', 'trees'],
        'crowds' => ['crowd', 'busy', 'quiet', 'solitude', 'people', 'line', 'arrive early', 'get there early'],
        'season' => ['spring', 'summer', 'fall', 'autumn', 'winter', 'snow', 'ice', 'icy', 'monsoon', 'best time', 'hot', 'cold'],
        'permits' => ['permit', 'lottery', 'reservation', 'ticket', 'fee', 'pass'],
        'navigation' => ['cairn', 'marked', 'unmarked', 'route finding', 'route-finding', 'lost', 'gps', 'trail marker', 'signs', 'signage', 'easy to follow', 'hard to follow'],
        'views' => ['view', 'overlook', 'vista', 'sunset', 'sunrise', 'photo', 'scenic', 'stunning', 'beautiful'],
        'access' => ['parking', 'trailhead', 'lot', 'shuttle', 'dirt road', '4wd', 'high clearance', 'high-clearance', '4x4', 'sand', 'washboard', 'road is'],
        'cell' => ['cell service', 'cell signal', 'reception', 'no service', 'signal'],
        'food' => ['menu', 'order', 'dish', 'vegetarian', 'vegan', 'gluten', 'kids menu', 'burger', 'breakfast', 'coffee', 'price', 'portion', 'wait', 'service', 'delicious'],
        'general' => [],
    ];

    /** Safety flags HikerAssessor treats as deal-breakers for kids and beginners. */
    public const FLAGS = [
        'exposure' => '/\b(exposed|exposure|drop-?offs?|sheer (cliff|drop)|cliff edges?)\b/i',
        'chains' => '/\b(chains?|cables?) (to hold|bolted|along|section)|\bhold(ing)? (on)?to (the )?chains\b/i',
        'scrambling' => '/\b(scrambl(e|es|ing)\b(?! to (get|see|find|reach|snag|book))|hands and feet|class 3)/i',
    ];

    public const MAX_POSTS = 40;
    public const SNIPPET_CHARS = 300;

    /**
     * @return array{facts: array, blog_excerpts: array, reviews: array, posts_considered: int, posts_with_matches: int}
     */
    public function about(string $table, int $id, string $name, ?int $areaId, string $topic = 'general', int $limit = 8, array $aliases = []): array
    {
        $topic = isset(self::TOPICS[$topic]) ? $topic : 'general';
        $key = 'chat:knowledge:' . md5(json_encode(func_get_args()));
        return Cache::remember($key, 3600, function() use ($table, $id, $name, $areaId, $topic, $limit, $aliases) {
            $facts = ResourceFact::where(['resource_table' => $table, 'resource_id' => $id])
                ->when($topic !== 'general', fn($q) => $q->where('topic', $topic))
                ->orderByDesc('source_date')->limit($limit * 2)->get()
                ->map(fn($fact) => [
                    'topic' => $fact->topic,
                    'claim' => $fact->claim,
                    'source' => $fact->source_type,
                    'url' => $fact->source_url,
                    'date' => $fact->source_date?->toDateString(),
                ])->all();

            [$excerpts, $considered, $matched] = $this->blogExcerpts($table, $id, $name, $areaId, $topic, $limit, $aliases);

            return [
                'topic' => $topic,
                'facts' => $facts,
                'blog_excerpts' => $excerpts,
                'reviews' => $this->reviews($table, $id, $topic, $limit),
                'posts_considered' => $considered,
                'posts_with_matches' => $matched,
            ];
        });
    }

    /** Reported exposure, chains and scrambling, from extracted facts and blog sentences about the record. */
    public function flags(string $table, int $id, string $name, ?int $areaId, array $aliases = []): array
    {
        $flags = [];
        $facts = ResourceFact::where(['resource_table' => $table, 'resource_id' => $id])
            ->whereIn('topic', ['exposure', 'scrambling', 'hazards'])->pluck('claim');
        $excerpts = array_merge(
            $this->about($table, $id, $name, $areaId, 'exposure', 12, $aliases)['blog_excerpts'],
            $this->about($table, $id, $name, $areaId, 'scrambling', 12, $aliases)['blog_excerpts'],
        );
        $texts = $facts->merge(array_column($excerpts, 'text'))->unique()->all();
        foreach (self::FLAGS as $flag => $pattern) {
            foreach ($texts as $text) {
                if (preg_match($pattern, $text) && !self::negated($text, $pattern)) {
                    $flags[$flag][] = Str::limit($text, 160);
                }
            }
        }
        return array_map(fn($claims) => array_values(array_unique($claims)), $flags);
    }

    private function blogExcerpts(string $table, int $id, string $name, ?int $areaId, string $topic, int $limit, array $aliases): array
    {
        $names = self::searchNames($name, $aliases);
        $linked = DB::table('blog_posts')->join('blogs', 'blogs.id', '=', 'blog_posts.blog_id')
            ->where(['resource_table' => $table, 'resource_id' => $id])
            ->orderByDesc('posted_at')->limit(self::MAX_POSTS)
            ->get(['blog_posts.id', 'blog_posts.title', 'blog_posts.content', 'blog_posts.raw_data', 'blog_posts.posted_at', 'blogs.name as blog', 'blogs.url as blog_url']);

        $mentioning = collect();
        if ($names) {
            $mentioning = DB::table('blog_posts')->join('blogs', 'blogs.id', '=', 'blog_posts.blog_id')
                ->where(fn($q) => $q->whereNull('resource_table')->orWhere('resource_table', '!=', $table)->orWhere('resource_id', '!=', $id))
                ->where(function($q) use ($names) {
                    foreach ($names as $n) {
                        $q->orWhere('blog_posts.title', 'like', '%' . $n . '%')->orWhere('blog_posts.content', 'like', '%' . $n . '%');
                    }
                })
                ->orderByDesc('posted_at')->limit(self::MAX_POSTS)
                ->get(['blog_posts.id', 'blog_posts.title', 'blog_posts.content', 'blog_posts.raw_data', 'blog_posts.posted_at', 'blogs.name as blog', 'blogs.url as blog_url']);
        }

        $areaWords = self::areaWords($areaId);
        $keywords = self::TOPICS[$topic];
        $excerpts = [];
        $matched = 0;
        foreach ([[$linked, true], [$mentioning, false]] as [$posts, $isLinked]) {
            foreach ($posts as $post) {
                $text = self::plain($post->content ?? '');
                // An unlinked post must name the record and its area, so a generic name like "Rim Trail" in a post
                // about another park doesn't count.
                if (!$isLinked && $areaWords && !self::containsAny($text . ' ' . $post->title, $areaWords)) {
                    continue;
                }
                $hits = [];
                foreach (self::sentences($text) as $sentence) {
                    $aboutIt = $isLinked || self::containsAny($sentence, $names);
                    $onTopic = !$keywords || self::containsAny($sentence, $keywords);
                    if ($aboutIt && $onTopic && mb_strlen($sentence) >= 40) {
                        $hits[] = $sentence;
                    }
                    if (count($hits) >= 2) {
                        break;
                    }
                }
                if ($hits) {
                    $matched++;
                    foreach ($hits as $hit) {
                        $excerpts[] = [
                            'text' => Str::limit($hit, self::SNIPPET_CHARS),
                            'post' => html_entity_decode($post->title, ENT_QUOTES),
                            'blog' => $post->blog,
                            'url' => self::postUrl($post),
                            'date' => substr((string) $post->posted_at, 0, 10),
                            'linked_to_record' => $isLinked,
                        ];
                    }
                }
            }
        }
        usort($excerpts, fn($a, $b) => [$b['linked_to_record'], $b['date']] <=> [$a['linked_to_record'], $a['date']]);
        return [array_slice($excerpts, 0, $limit), $linked->count() + $mentioning->count(), $matched];
    }

    private function reviews(string $table, int $id, string $topic, int $limit): array
    {
        $keywords = self::TOPICS[$topic];
        $tables = $table === 'restaurants' ? ['restaurants', 'locations'] : [$table];
        $ids = [$id];
        if ($table === 'restaurants' && ($locationId = DB::table('restaurants')->where('id', $id)->value('location_id'))) {
            $ids[] = $locationId;
        }
        return DB::table('resource_reviews')->whereIn('resource_table', $tables)->whereIn('resource_id', $ids)
            ->where('is_usable', 1)->orderByDesc('posted_at')->limit(60)->get()
            ->filter(fn($review) => !$keywords || self::containsAny($review->comment, $keywords))
            ->take($limit)
            ->map(fn($review) => [
                'text' => Str::limit(self::plain($review->comment), self::SNIPPET_CHARS),
                'source' => $review->source_type,
                'date' => $review->posted_at,
            ])->values()->all();
    }

    /** The name and its useful variants: "Canyon Overlook Trail" also matches "Canyon Overlook". */
    public static function searchNames(string $name, array $aliases = []): array
    {
        $names = [];
        foreach ([$name, ...$aliases] as $candidate) {
            $candidate = trim(preg_replace('/\s*#\s*\d+.*$/', '', html_entity_decode((string) $candidate)));
            if ($candidate === '') {
                continue;
            }
            $names[] = $candidate;
            $short = trim(preg_replace('/\b(trail|trailhead|loop|hike|route)\b\.?$/i', '', $candidate));
            if ($short !== $candidate && mb_strlen($short) >= 6 && str_word_count($short) >= 2) {
                $names[] = $short;
            }
        }
        return array_values(array_unique(array_filter($names, fn($n) => mb_strlen($n) >= 5)));
    }

    private static function areaWords(?int $areaId): array
    {
        if (!$areaId || !($area = Area::find($areaId))) {
            return [];
        }
        $words = [$area->short_name ?: $area->name];
        $words[] = trim(preg_replace('/\b(national park|utah|arizona|canyon)\b|[(),.]/i', ' ', $area->name));
        return array_values(array_filter(array_unique(array_map('trim', $words)), fn($w) => mb_strlen($w) >= 4));
    }

    public static function plain(string $html): string
    {
        $text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html);
        $text = preg_replace('/<\/(p|h\d|li|div|br)\s*>|<br\s*\/?>/i', ". ", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/(\.\s*){2,}/', '. ', $text)));
    }

    public static function sentences(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/(?<=[.!?])\s+(?=[A-Z0-9"“])/u', $text))));
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_stripos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function negated(string $text, string $pattern): bool
    {
        if (!preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        $before = mb_strtolower(substr($text, max(0, $m[0][1] - 25), min(25, $m[0][1])));
        return (bool) preg_match('/\b(no|not|without|isn\'t|aren\'t|never|minimal|little)\b/', $before);
    }

    private static function postUrl(object $post): ?string
    {
        $raw = json_decode($post->raw_data ?? '', true);
        $link = is_array($raw) ? ($raw['link'] ?? $raw['url'] ?? null) : null;
        return is_string($link) && preg_match('#^https?://#', $link) ? $link : ($post->blog_url ?? null);
    }
}
