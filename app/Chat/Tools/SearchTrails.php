<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\TrailFinder;

class SearchTrails extends BaseTool
{
    public function __construct(private TrailFinder $finder)
    {
    }

    public function name(): string
    {
        return 'search_trails';
    }

    public function description(): string
    {
        return 'Find trails by name (typos and partial names are fine) and/or filters: area, distance, climb, difficulty, '
            . 'activity (hiking, bicycling, offroading, swimming, rock-climbing, stargazing...), a feature word (arch, slot, waterfall, petroglyph...), free, '
            . 'dogs allowed, or near a place. Use it first whenever a trail is named. If "ambiguous" is true and the traveler '
            . 'did not say which one, call present_options with the matches. Duplicate records of one trail are folded together.';
    }

    public function parameters(): array
    {
        return self::object([
            'query' => ['type' => 'string', 'description' => 'Trail name or part of it.'],
            'area_id' => ['type' => 'integer'],
            'near' => self::object(Geo::placeSchema()) + ['description' => 'Rank by distance from this place.'],
            'within_miles' => ['type' => 'number', 'description' => 'With near: straight-line radius, default 40.'],
            'max_miles' => ['type' => 'number', 'description' => 'Maximum listed distance.'],
            'min_miles' => ['type' => 'number'],
            'max_climb_ft' => ['type' => 'integer'],
            'difficulty' => ['type' => 'string', 'description' => 'Easy, Easy/Intermediate, Intermediate, Intermediate/Difficult, Difficult, Very Difficult'],
            'activity' => ['type' => 'string'],
            'feature' => ['type' => 'string'],
            'free_only' => ['type' => 'boolean'],
            'dogs_allowed' => ['type' => 'boolean'],
            'limit' => ['type' => 'integer', 'description' => 'Default 8, at most 20.'],
            'offset' => ['type' => 'integer', 'description' => 'For "show me more".'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'query' => 'nullable|string|max:120',
            'area_id' => 'nullable|integer',
            'near' => 'nullable|array',
            'within_miles' => 'nullable|numeric|min:1|max:200',
            'max_miles' => 'nullable|numeric|min:0',
            'min_miles' => 'nullable|numeric|min:0',
            'max_climb_ft' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|string|max:40',
            'activity' => 'nullable|string|max:40',
            'feature' => 'nullable|string|max:40',
            'free_only' => 'nullable|boolean',
            'dogs_allowed' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:20',
            'offset' => 'nullable|integer|min:0|max:200',
        ]);

        $filters = array_filter([
            'area_id' => $args['area_id'] ?? null,
            'max_miles' => $args['max_miles'] ?? null,
            'min_miles' => $args['min_miles'] ?? null,
            'max_gain_ft' => $args['max_climb_ft'] ?? null,
            'difficulty' => $args['difficulty'] ?? null,
            'activity' => $args['activity'] ?? null,
            'feature' => $args['feature'] ?? null,
            'free_only' => $args['free_only'] ?? null,
            'dogs_allowed' => $args['dogs_allowed'] ?? null,
            'within_miles' => $args['within_miles'] ?? null,
        ], fn($v) => $v !== null && $v !== false);

        $nearLabel = null;
        if (!empty($args['near']) && ($place = Geo::resolve($args['near'], $context))) {
            $filters['near'] = $place['point'];
            $nearLabel = $place['label'];
        }

        $limit = $args['limit'] ?? 8;
        $offset = $args['offset'] ?? 0;
        $result = $this->finder->search($args['query'] ?? null, $filters, $limit + $offset);
        $matches = array_slice($result['matches'], $offset, $limit);

        return ToolResult::ok([
            'matches' => $matches,
            'count' => count($matches),
            'ambiguous' => $result['ambiguous'],
            'near' => $nearLabel,
            'duplicates_folded' => $result['duplicates_folded'],
            'note' => $matches ? null : 'No trails matched. Say so, and try a broader search before suggesting anything.',
        ], $matches ? [ToolResult::source('Trail records', null, ToolResult::OUR_DATA)] : []);
    }
}
