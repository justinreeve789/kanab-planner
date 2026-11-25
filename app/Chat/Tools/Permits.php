<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\Knowledge;
use App\Chat\Live\RidbClient;

class Permits extends BaseTool
{
    public function __construct(private RidbClient $ridb, private Knowledge $knowledge)
    {
    }

    public function name(): string
    {
        return 'permits';
    }

    public function description(): string
    {
        return 'Whether a trail or place needs a permit, lottery ticket, timed entry or reservation, what it costs and '
            . 'where to apply: our trail record, Recreation.gov listings near it, and what blogs say about getting one. '
            . 'Lottery dates change every year: give the official page and say to confirm dates there.';
    }

    public function parameters(): array
    {
        return self::object([
            'trail_id' => ['type' => 'integer'],
            'query' => ['type' => 'string', 'description' => 'Name to look up when there is no trail record, e.g. "The Wave", "Antelope Canyon".'],
            'area_id' => ['type' => 'integer'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'trail_id' => 'nullable|integer|required_without:query',
            'query' => 'nullable|string|max:80',
            'area_id' => 'nullable|integer',
        ]);
        $data = [];
        $sources = [];
        $name = $args['query'] ?? null;
        $place = null;

        if (!empty($args['trail_id'])) {
            if (!($trail = $this->trail($args['trail_id']))) {
                return $this->notFound('trail', $args['trail_id']);
            }
            $name = html_entity_decode($trail->name, ENT_QUOTES);
            $data['trail_record'] = [
                'name' => $name,
                'permit_or_reservation' => $trail->reservation === null ? 'unknown' : (bool) $trail->reservation,
                'fee' => $trail->fee === null ? 'unknown' : (bool) $trail->fee,
                'official_url' => $trail->url,
            ];
            $sources[] = $this->trailSource($trail);
            $place = Geo::resolve(['trail_id' => $trail->id]);
            $blog = $this->knowledge->about('trails', $trail->id, $name, $trail->area_id, 'permits', 5, array_filter([$trail->name_alt], fn($a) => preg_match('/[a-z]{3}/i', (string) $a)));
            $data['what_blogs_say'] = array_map(fn($e) => ['text' => $e['text'], 'date' => $e['date'], 'blog' => $e['blog']], $blog['blog_excerpts']);
            foreach (array_slice($blog['blog_excerpts'], 0, 2) as $e) {
                $sources[] = ToolResult::source("{$e['blog']}: {$e['post']}", $e['url'], ToolResult::BLOG, $e['date']);
            }
        }
        $place ??= !empty($args['area_id']) ? Geo::resolve(['area_id' => $args['area_id']]) : null;

        if ($this->ridb->enabled() && $place && $name) {
            $facilities = $this->ridb->facilities($name, $place['point']->lat, $place['point']->lng, 40, 5);
            $data['recreation_gov'] = $facilities ?? 'Could not reach Recreation.gov right now.';
            if ($facilities) {
                $sources[] = ToolResult::source('Recreation.gov', $facilities[0]['url'] ?? 'https://www.recreation.gov/', ToolResult::OFFICIAL);
            }
        }
        $data['search_official'] = 'https://www.recreation.gov/search?q=' . rawurlencode((string) $name);
        $data['note'] = 'Confirm current lottery dates and fees on the official page; they change yearly.';

        return ToolResult::ok($data, $sources);
    }
}
