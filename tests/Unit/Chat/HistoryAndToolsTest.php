<?php

namespace Tests\Unit\Chat;

use App\Chat\Data\TrailFinder;
use App\Chat\History;
use App\Chat\Tools\SunTimes;
use App\Chat\Tools\ToolRegistry;
use App\Chat\Tools\ToolResult;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class HistoryAndToolsTest extends TestCase
{
    public function test_history_keeps_recent_exchanges_whole_and_shortens_old_results(): void
    {
        $items = [];
        for ($i = 1; $i <= 15; $i++) {
            $items[] = ['type' => 'user', 'text' => "q{$i}"];
            $items[] = ['type' => 'tool_call', 'id' => "c{$i}", 'name' => 't', 'arguments' => []];
            $items[] = ['type' => 'tool_result', 'id' => "c{$i}", 'name' => 't', 'output' => str_repeat('x', 2000)];
            $items[] = ['type' => 'assistant', 'text' => "a{$i}"];
        }
        $replay = History::forModel($items);

        $this->assertSame('q6', $replay[0]['text']); // last 10 exchanges
        $results = array_values(array_filter($replay, fn($i) => $i['type'] === 'tool_result'));
        $this->assertLessThan(700, mb_strlen($results[0]['output']));
        $this->assertSame(2000, mb_strlen(end($results)['output']));
    }

    public function test_history_drops_unpaired_tool_calls(): void
    {
        $replay = History::forModel([
            ['type' => 'user', 'text' => 'hi'],
            ['type' => 'tool_call', 'id' => 'orphan', 'name' => 't', 'arguments' => []],
            ['type' => 'tool_result', 'id' => 'stray', 'name' => 't', 'output' => '{}'],
            ['type' => 'assistant', 'text' => 'hello'],
            ['type' => 'provider', 'driver' => 'openai', 'item' => []],
        ]);
        $this->assertSame(['user', 'assistant'], array_column($replay, 'type'));
    }

    public function test_every_tool_has_a_valid_definition(): void
    {
        $definitions = ToolRegistry::default()->definitions();
        $this->assertCount(count(ToolRegistry::DEFAULT), $definitions);
        foreach ($definitions as $tool) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $tool['name']);
            $this->assertGreaterThan(60, strlen($tool['description']), $tool['name']);
            $this->assertSame('object', $tool['parameters']['type'], $tool['name']);
            // Must survive JSON encoding as an object (Anthropic rejects "properties": []).
            $this->assertStringStartsWith('{', json_encode($tool['parameters']['properties']), $tool['name']);
        }
    }

    public function test_every_tool_named_in_the_question_templates_exists(): void
    {
        $templates = json_decode(file_get_contents(resource_path('chat/question_templates.json')), true);
        $this->assertCount(300, $templates);
        $named = array_unique(array_merge(...array_column($templates, 'tools')));
        $this->assertSame([], array_values(array_diff($named, ToolRegistry::default()->names())));
    }

    public function test_tool_output_is_capped(): void
    {
        $result = ToolResult::ok(['text' => str_repeat('a', 10000)]);
        $this->assertLessThanOrEqual(520, mb_strlen($result->toModelOutput(500)));
        $this->assertStringEndsWith('[truncated]', $result->toModelOutput(500));
    }

    public function test_typo_tolerant_name_scores(): void
    {
        $this->assertSame(1.0, TrailFinder::score('Navajo Loop', 'Navajo Loop'));
        $this->assertSame(1.0, TrailFinder::score('angels landing trail', "Angel's Landing"));
        $this->assertGreaterThan(0.7, TrailFinder::score('angles landing', "Angel's Landing Trail"));
        $this->assertGreaterThan(0.55, TrailFinder::score('Overlook', 'Canyon Overlook Trail'));
        $this->assertSame(0.0, TrailFinder::score('k', 'Sheep Creek Trail'));
        $this->assertSame(1, TrailFinder::typoDistance('angles', 'angels'));
    }

    public function test_sun_times_use_arizona_time_without_daylight_saving(): void
    {
        $day = CarbonImmutable::parse('2026-07-01');
        $page = SunTimes::day($day, 36.91, -111.46, 'America/Phoenix');   // Page, AZ
        $kanab = SunTimes::day($day, 37.05, -112.53, 'America/Denver');   // Kanab, UT
        // Nearly the same sun, but Kanab's clock is an hour ahead in summer (plus ~4 minutes for longitude).
        $minutes = fn($t) => (int) date('G', strtotime($t)) * 60 + (int) date('i', strtotime($t));
        $this->assertStringEndsWith('PM', $page['sunset']);
        $this->assertSame(7, (int) date('g', strtotime($page['sunset'])));
        $this->assertEqualsWithDelta(64, $minutes($kanab['sunset']) - $minutes($page['sunset']), 6);
    }

    public function test_moon_phase_is_close_to_known_dates(): void
    {
        // Full moon 2026-12-24 (approximately); new moon 2026-12-09.
        $this->assertSame('full moon', SunTimes::moon(CarbonImmutable::parse('2026-12-24'))['phase']);
        $this->assertSame('new moon', SunTimes::moon(CarbonImmutable::parse('2026-12-09'))['phase']);
    }
}
