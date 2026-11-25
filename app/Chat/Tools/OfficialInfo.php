<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Live\NpsClient;

class OfficialInfo extends BaseTool
{
    /** Topics the model can ask about. Only pages that exist for every park get their own link. */
    public const TOPICS = ['fees', 'hours', 'shuttle', 'pets', 'accessibility', 'permits', 'rules', 'camping', 'conditions', 'kids'];
    public const PARK_PAGES = [
        'fees' => 'planyourvisit/fees.htm',
        'conditions' => 'planyourvisit/conditions.htm',
    ];

    public function __construct(private NpsClient $nps)
    {
    }

    public function name(): string
    {
        return 'official_info';
    }

    public function description(): string
    {
        return 'Official park information from the National Park Service: entrance fees and passes, operating hours, '
            . 'and park "things to do" entries matching words (a trail, the shuttle, Junior Ranger, drones, pets, '
            . 'accessibility), plus the official page for a topic (fees, shuttle, pets, accessibility, permits, rules, '
            . 'camping, conditions, kids, hours). Rules on BLM, state or tribal land aren\'t covered: say so and give the managing agency.';
    }

    public function parameters(): array
    {
        return self::object([
            'area_id' => ['type' => 'integer', 'description' => 'Area whose park to ask about.'],
            'park' => ['type' => 'string', 'enum' => ['zion', 'brca', 'grca', 'glca'], 'description' => 'NPS park code, if not using area_id.'],
            'topic' => ['type' => 'string', 'enum' => self::TOPICS],
            'query' => ['type' => 'string', 'description' => 'Words to look up in the park\'s things to do, e.g. "Angels Landing", "drone".'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'area_id' => 'nullable|integer',
            'park' => 'nullable|in:zion,brca,grca,glca',
            'topic' => 'nullable|in:' . implode(',', self::TOPICS),
            'query' => 'nullable|string|max:80',
        ]);
        $park = $args['park'] ?? (config('chat.areas.' . ($args['area_id'] ?? 0) . '.parks')[0] ?? null);
        if (!$park) {
            return ToolResult::unavailable('That area is not a national park unit, so there is no NPS information. Rules come from the managing agency (BLM, the state, the city or a tribe).');
        }
        $page = NpsClient::parkUrl($park, self::PARK_PAGES[$args['topic'] ?? ''] ?? 'planyourvisit/index.htm');
        $sources = [ToolResult::source('National Park Service: ' . strtoupper($park), $page, ToolResult::OFFICIAL)];

        if (!$this->nps->enabled()) {
            return ToolResult::unavailable('The park service API isn\'t connected here, so give the official page instead of specifics.', $page);
        }

        $data = ['park' => $park, 'official_page' => $page];
        $info = $this->nps->park($park);
        if ($info) {
            $data['name'] = $info['name'];
            if (in_array($args['topic'] ?? 'fees', ['fees', 'hours'], true) || empty($args['query'])) {
                $data['entrance_fees'] = $info['fees'];
                $data['passes'] = $info['passes'];
                $data['operating_hours'] = $info['hours'];
            }
        }
        if (!empty($args['query'])) {
            $data['things_to_do'] = $this->nps->thingsToDo($park, $args['query']) ?? 'Could not reach the park service right now.';
        }
        if (!$info && empty($data['things_to_do'])) {
            return ToolResult::unavailable('Could not reach the National Park Service right now.', $page);
        }
        return ToolResult::ok($data, $sources);
    }
}
