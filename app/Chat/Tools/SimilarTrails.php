<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\TrailFinder;
use App\Services\Routing\Point;

class SimilarTrails extends BaseTool
{
    public function __construct(private TrailFinder $finder)
    {
    }

    public function name(): string
    {
        return 'similar_trails';
    }

    public function description(): string
    {
        return 'Alternatives to a trail nearby: similar, easier or shorter, or less crowded (by the solitude score). Use '
            . 'for "anything easier nearby?", backup plans when a trail is closed, and "like X but quieter".';
    }

    public function parameters(): array
    {
        return self::object([
            'trail_id' => ['type' => 'integer'],
            'want' => ['type' => 'string', 'enum' => ['similar', 'easier', 'shorter', 'quieter', 'harder']],
            'within_miles' => ['type' => 'number', 'description' => 'Default 25.'],
            'limit' => ['type' => 'integer', 'description' => 'Default 5.'],
        ], ['trail_id']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'trail_id' => 'required|integer',
            'want' => 'nullable|in:similar,easier,shorter,quieter,harder',
            'within_miles' => 'nullable|numeric|min:1|max:150',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);
        if (!($trail = $this->trail($args['trail_id']))) {
            return $this->notFound('trail', $args['trail_id']);
        }
        $want = $args['want'] ?? 'similar';
        $origin = Point::make($trail->latitude, $trail->longitude) ?? \App\Chat\Data\Geo::trailStart($trail->id);
        $filters = ['exclude_ids' => [$trail->id], 'within_miles' => $args['within_miles'] ?? 25];
        if ($origin) {
            $filters['near'] = $origin;
        } else {
            $filters['area_id'] = $trail->area_id;
        }
        $distance = (float) ($trail->distance_miles ?? 0);
        if (in_array($want, ['easier', 'shorter'], true) && $distance > 0) {
            $filters['max_miles'] = $want === 'shorter' ? $distance * 0.75 : $distance;
        }

        $candidates = $this->finder->search(null, $filters, 60)['matches'];
        $base = (float) ($trail->incline_ft ?? 0);
        $scored = array_map(function($m) use ($want, $distance, $base) {
            $miles = $m['distance_miles'] ?? $distance;
            $climb = (float) ($m['climb_ft'] ?? $base);
            $strenuous = $m['scores']['strenuous'] ?? 0.5;
            $solitude = $m['scores']['solitude'] ?? 0.5;
            $away = $m['miles_away'] ?? 10;
            $score = match ($want) {
                'easier' => -$strenuous * 2 - $climb / 2000 - $away / 50 + ($climb <= $base ? 1 : 0),
                'shorter' => -abs($miles - $distance * 0.5) - $away / 50,
                'quieter' => $solitude * 2 - $away / 50,
                'harder' => $strenuous * 2 + $climb / 2000 - $away / 50,
                default => -abs($miles - $distance) / max(1, $distance) - abs($climb - $base) / max(300, $base) - $away / 30,
            };
            return $m + ['_score' => $score];
        }, $candidates);
        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
        $picks = array_map(fn($m) => array_diff_key($m, ['_score' => 1, 'match_score' => 1]), array_slice($scored, 0, $args['limit'] ?? 5));

        return ToolResult::ok([
            'compared_to' => ['trail_id' => $trail->id, 'name' => html_entity_decode($trail->name, ENT_QUOTES), 'distance_miles' => $trail->distance_miles, 'climb_ft' => $trail->incline_ft],
            'want' => $want,
            'alternatives' => $picks,
        ], [ToolResult::source('Trail records', null, ToolResult::OUR_DATA)]);
    }
}
