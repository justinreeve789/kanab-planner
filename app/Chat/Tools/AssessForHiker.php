<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\HikerAssessor;
use App\Chat\Data\Knowledge;
use App\Chat\Data\TripShape;
use App\Models\TrailProfile;

class AssessForHiker extends BaseTool
{
    public function __construct(private Knowledge $knowledge)
    {
    }

    public function name(): string
    {
        return 'assess_for_hiker';
    }

    public function description(): string
    {
        return 'Judge whether a trail suits a particular hiker or group: a child of a given age, a beginner, a fit adult, '
            . 'someone with bad knees or limited mobility, an older adult. Returns a verdict (good, doable_with_caveats, '
            . 'not_recommended), each limit that is exceeded with the measured value and where on the trail, a turnaround '
            . 'point that avoids the hard section when there is one, and reported exposure or scrambling from blogs. '
            . 'Use for every "can my kid / can I / is it too hard" question; quote its evidence.';
    }

    public function parameters(): array
    {
        return self::object([
            'trail_id' => ['type' => 'integer'],
            'hikers' => [
                'type' => 'array',
                'description' => 'Each person to judge (the weakest decides the group). Give age, fitness, or both.',
                'items' => self::object([
                    'age' => ['type' => 'integer'],
                    'fitness' => ['type' => 'string', 'enum' => ['beginner', 'moderate', 'fit', 'limited']],
                    'label' => ['type' => 'string', 'description' => 'e.g. "your 5-year-old"'],
                ]),
            ],
            'plan' => ['type' => 'string', 'enum' => ['round_trip', 'one_way'], 'description' => 'Default round_trip (back to the trailhead).'],
        ], ['trail_id', 'hikers']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'trail_id' => 'required|integer',
            'hikers' => 'required|array|min:1|max:8',
            'hikers.*.age' => 'nullable|integer|min:0|max:110',
            'hikers.*.fitness' => 'nullable|in:beginner,moderate,fit,limited',
            'hikers.*.label' => 'nullable|string|max:60',
            'plan' => 'nullable|in:round_trip,one_way',
        ]);
        if (!($trail = $this->trail($args['trail_id']))) {
            return $this->notFound('trail', $args['trail_id']);
        }

        $assessor = HikerAssessor::fromConfig();
        $profile = TrailProfile::forTrail($trail->id);
        $name = html_entity_decode($trail->name, ENT_QUOTES);
        $flags = $this->knowledge->flags('trails', $trail->id, $name, $trail->area_id, array_filter([$trail->name_alt], fn($a) => preg_match('/[a-z]{3}/i', (string) $a)));

        $results = [];
        foreach ($args['hikers'] as $hiker) {
            $band = $assessor->bandFor($hiker['age'] ?? null, $hiker['fitness'] ?? null);
            $trip = TripShape::for($trail, $profile, $band, $args['plan'] ?? 'round_trip');
            $results[] = ['who' => $hiker['label'] ?? null, 'trip' => $trip] + $assessor->assess($band, $trip, $profile, $flags);
        }

        $order = [HikerAssessor::GOOD => 0, HikerAssessor::CAVEATS => 1, HikerAssessor::NO => 2];
        $group = array_reduce($results, fn($carry, $r) => $order[$r['verdict']] > $order[$carry] ? $r['verdict'] : $carry, HikerAssessor::GOOD);

        $sources = [ToolResult::source($name . ($profile ? ' GPS track profile' : ' (our trail record)'), $trail->url ?: null, ToolResult::OUR_DATA)];
        foreach (array_merge(...array_values($flags) ?: [[]]) as $claim) {
            $sources[] = ToolResult::source('Travel blog reports about ' . $name, null, ToolResult::BLOG);
            break;
        }

        return ToolResult::ok([
            'trail' => $name,
            'group_verdict' => $group,
            'hikers' => $results,
            'steepest_sections' => $profile ? array_slice($profile['very_steep_sections'] ?: $profile['steep_sections'], 0, 4) : null,
            'reported_flags' => $flags ?: null,
        ], $sources);
    }
}
