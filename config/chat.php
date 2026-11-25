<?php

/*
| The trip assistant (app/Chat). A tool-calling model answers from the planner's own data and live feeds; see
| app/Chat/ChatAgent.php for the loop and app/Chat/Tools for what it can look up.
*/

return [

    // openai (Responses API) or anthropic (Messages API). Both are called over plain HTTP, so tests fake them.
    'driver' => env('CHAT_DRIVER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('CHAT_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('CHAT_OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4.1-mini')),
        // Reasoning models only: low | medium | high. Leave empty for non-reasoning models.
        'reasoning_effort' => env('CHAT_OPENAI_REASONING_EFFORT'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('CHAT_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'model' => env('CHAT_ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
        'version' => '2023-06-01',
    ],

    // Guards on one reply: model round trips, tokens per round trip and wall time for the whole reply.
    'max_steps' => (int) env('CHAT_MAX_STEPS', 8),
    'max_output_tokens' => (int) env('CHAT_MAX_OUTPUT_TOKENS', 1200),
    'timeout_seconds' => (int) env('CHAT_TIMEOUT_SECONDS', 60),

    // How much of a thread is replayed to the model. Older tool results are shortened first.
    'history_messages' => 20,
    'tool_result_max_chars' => 6000,

    'timezone' => 'America/Denver',

    // Live sources. Each is optional: a tool without its key says it can't check and links the official page.
    'sources' => [
        'nws_user_agent' => env('NWS_USER_AGENT', '(kanab-planner, admin@example.com)'),
        'nps_api_key' => env('NPS_API_KEY'),
        'ridb_api_key' => env('RIDB_API_KEY'),
        'airnow_api_key' => env('AIRNOW_API_KEY'),
        'usgs_api_key' => env('USGS_API_KEY'),
        'cache_minutes' => [
            'forecast' => 30,
            'alerts' => 15,
            'river_flow' => 15,
            'nps' => 60,
            'permits' => 360,
            'air_quality' => 60,
        ],
    ],

    // area id => NPS park codes, river gauges and the official conditions page.
    'areas' => [
        1 => ['parks' => ['brca'], 'conditions_url' => 'https://www.nps.gov/brca/planyourvisit/conditions.htm'],
        2 => ['parks' => ['grca'], 'conditions_url' => 'https://www.nps.gov/grca/planyourvisit/conditions.htm'],
        3 => ['parks' => [], 'conditions_url' => null],
        4 => ['parks' => ['glca'], 'conditions_url' => 'https://www.nps.gov/glca/planyourvisit/conditions.htm'],
        5 => ['parks' => ['glca'], 'conditions_url' => 'https://www.nps.gov/glca/planyourvisit/conditions.htm'],
        6 => ['parks' => [], 'conditions_url' => null],
        7 => ['parks' => ['zion'], 'conditions_url' => 'https://www.nps.gov/zion/planyourvisit/conditions.htm'],
    ],

    // Named river gauges. The Narrows rules come from Zion's own conditions page.
    'gauges' => [
        'virgin-river-zion' => [
            'site' => 'USGS-09405500',
            'name' => 'North Fork Virgin River near Springdale',
            'thresholds' => [
                ['cfs' => 150, 'meaning' => 'The Narrows closes to all hiking above 150 CFS.'],
                ['cfs' => 120, 'meaning' => 'Top-down Narrows trips close above 120 CFS.'],
                ['cfs' => 70, 'meaning' => 'Above about 70 CFS, walking against the current is hard and crossings are often thigh-deep.'],
            ],
            'official_url' => 'https://www.nps.gov/zion/planyourvisit/thenarrows.htm',
            'match' => ['narrows', 'virgin river', 'orderville', 'temple of sinawava'],
        ],
    ],

    /*
    | Hiker limits used by assess_for_hiker. Numbers are a starting point to tune against trails you know, not
    | medical advice. "sustained" is the steepest 0.1 mile; "pitch" is the steepest 0.05 mile.
    */
    'hikers' => [
        'carried' => ['label' => 'a child carried in a pack (under 4)', 'max_miles' => 3, 'max_gain_ft' => 800, 'max_sustained_pct' => 20, 'max_pitch_pct' => 28, 'max_hours' => 3, 'exposure_ok' => false],
        'age_4_6' => ['label' => 'a 4- to 6-year-old', 'max_miles' => 3, 'max_gain_ft' => 600, 'max_sustained_pct' => 15, 'max_pitch_pct' => 25, 'max_hours' => 3, 'exposure_ok' => false],
        'age_7_9' => ['label' => 'a 7- to 9-year-old', 'max_miles' => 5, 'max_gain_ft' => 1200, 'max_sustained_pct' => 20, 'max_pitch_pct' => 30, 'max_hours' => 4, 'exposure_ok' => false],
        'age_10_12' => ['label' => 'a 10- to 12-year-old', 'max_miles' => 7, 'max_gain_ft' => 2000, 'max_sustained_pct' => 25, 'max_pitch_pct' => 35, 'max_hours' => 6, 'exposure_ok' => false],
        'teen' => ['label' => 'a teenager', 'max_miles' => 10, 'max_gain_ft' => 3000, 'max_sustained_pct' => 30, 'max_pitch_pct' => 40, 'max_hours' => 8, 'exposure_ok' => true],
        'beginner' => ['label' => 'a beginner adult', 'max_miles' => 5, 'max_gain_ft' => 1200, 'max_sustained_pct' => 20, 'max_pitch_pct' => 30, 'max_hours' => 4, 'exposure_ok' => false],
        'moderate' => ['label' => 'a reasonably fit adult', 'max_miles' => 9, 'max_gain_ft' => 2500, 'max_sustained_pct' => 30, 'max_pitch_pct' => 40, 'max_hours' => 7, 'exposure_ok' => true],
        'fit' => ['label' => 'a very fit adult', 'max_miles' => 14, 'max_gain_ft' => 5000, 'max_sustained_pct' => 40, 'max_pitch_pct' => 55, 'max_hours' => 11, 'exposure_ok' => true],
        'limited' => ['label' => 'someone with limited mobility, bad knees or low stamina', 'max_miles' => 3, 'max_gain_ft' => 500, 'max_sustained_pct' => 12, 'max_pitch_pct' => 18, 'max_hours' => 2.5, 'exposure_ok' => false],
    ],

    // Steepness bands reported by trail_profile, in percent grade.
    'steep_pct' => 15,
    'very_steep_pct' => 25,
    'gentle_pct' => 8,

    // Placeholder vocabularies for the eval templates (resources/chat/question_templates.json).
    'eval' => [
        'templates_path' => resource_path('chat/question_templates.json'),
        'report_dir' => storage_path('app/chat-eval'),
        'values' => [
            'age' => [3, 5, 8, 11, 15, 70],
            'activity' => ['canyoneering', 'mountain biking', 'kayaking', 'stargazing', 'rock climbing', 'photography'],
            'feature' => ['petroglyphs', 'arches', 'slot canyons', 'waterfalls', 'hoodoos', 'dinosaur tracks'],
            'gear' => ['trekking poles', 'microspikes', 'a helmet', 'water shoes', 'a dry suit', 'a headlamp'],
            'diet' => ['Vegetarian', 'Vegan', 'Gluten-free'],
            'cuisine' => ['Mexican', 'pizza', 'barbecue', 'Thai', 'breakfast'],
            'amenity' => ['RV hookups', 'showers', 'shade', 'reservations'],
            'vehicle' => ['regular sedan', 'AWD SUV', 'high-clearance 4x4'],
            'difficulty' => ['beginner', 'intermediate', 'advanced'],
            'season' => ['spring', 'summer', 'fall', 'winter'],
            'group' => ['two adults and a 6-year-old', 'four friends in their 20s', 'grandparents and two teens', 'a couple with a dog'],
            'holiday' => ['Memorial Day', 'Fourth of July', 'Labor Day', 'Thanksgiving'],
            'park' => ['Zion National Park', 'Bryce Canyon National Park', 'Grand Canyon North Rim', 'Glen Canyon'],
            'N' => [2, 3, 4, 5],
        ],
    ],
];
