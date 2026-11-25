<?php

namespace Tests\Unit\Chat;

use App\Chat\Models\AnthropicMessagesModel;
use App\Chat\Models\ModelError;
use App\Chat\Models\OpenAIResponsesModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModelDriversTest extends TestCase
{
    private const TOOLS = [[
        'name' => 'search_trails',
        'description' => 'Find trails',
        'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]],
    ]];

    private function transcript(): array
    {
        return [
            ['type' => 'user', 'text' => 'How steep is Angels Landing?'],
            ['type' => 'tool_call', 'id' => 'c1', 'name' => 'search_trails', 'arguments' => ['query' => 'Angels Landing']],
            ['type' => 'tool_result', 'id' => 'c1', 'name' => 'search_trails', 'output' => '{"matches":[]}'],
        ];
    }

    public function test_openai_request_shape_and_tool_call_parsing(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.openai.com/v1/responses' => Http::response(['output' => [
            ['type' => 'reasoning', 'id' => 'rs_1', 'encrypted_content' => 'xyz'],
            ['type' => 'function_call', 'call_id' => 'c2', 'name' => 'trail_profile', 'arguments' => '{"trail_id":212}'],
        ], 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]])]);

        $turn = (new OpenAIResponsesModel('sk-test', 'gpt-test', reasoningEffort: 'low'))
            ->respond('You help hikers.', $this->transcript(), self::TOOLS, 500);

        $this->assertSame([['type' => 'tool_call', 'id' => 'c2', 'name' => 'trail_profile', 'arguments' => ['trail_id' => 212]]], $turn->toolCalls());
        $this->assertSame('provider', $turn->items[0]['type']);
        $this->assertSame(100, $turn->usage['input_tokens']);

        Http::assertSent(function(Request $request) {
            $body = $request->data();
            return $request->hasHeader('Authorization', 'Bearer sk-test')
                && $body['instructions'] === 'You help hikers.'
                && $body['store'] === false
                && $body['reasoning'] === ['effort' => 'low']
                && $body['input'][0] === ['role' => 'user', 'content' => 'How steep is Angels Landing?']
                && $body['input'][1]['type'] === 'function_call' && $body['input'][1]['arguments'] === '{"query":"Angels Landing"}'
                && $body['input'][2] === ['type' => 'function_call_output', 'call_id' => 'c1', 'output' => '{"matches":[]}']
                && $body['tools'][0]['type'] === 'function' && $body['tools'][0]['name'] === 'search_trails'
                && !isset($body['tool_choice']);
        });
    }

    public function test_openai_final_call_forbids_tools_and_reads_text(): void
    {
        Http::fake(['*' => Http::response(['output' => [
            ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'About 1,500 feet of climbing.']]],
        ]])]);
        $turn = (new OpenAIResponsesModel('k', 'm'))->respond('s', $this->transcript(), self::TOOLS, 500, allowTools: false);

        $this->assertSame('About 1,500 feet of climbing.', $turn->text());
        Http::assertSent(fn(Request $r) => $r->data()['tool_choice'] === 'none');
    }

    public function test_anthropic_merges_turns_and_sends_tool_results_as_user_content(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.anthropic.com/v1/messages' => Http::response(['content' => [
            ['type' => 'text', 'text' => 'Let me check the profile.'],
            ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'trail_profile', 'input' => ['trail_id' => 212]],
        ], 'usage' => ['input_tokens' => 50, 'output_tokens' => 10]])]);

        $turn = (new AnthropicMessagesModel('ak-test', 'claude-test'))->respond('sys', $this->transcript(), self::TOOLS, 500);

        $this->assertSame('Let me check the profile.', $turn->text());
        $this->assertSame('tu_1', $turn->toolCalls()[0]['id']);
        Http::assertSent(function(Request $request) {
            $body = $request->data();
            return $request->hasHeader('x-api-key', 'ak-test')
                && $body['system'] === 'sys'
                && count($body['messages']) === 3
                && $body['messages'][1]['role'] === 'assistant' && $body['messages'][1]['content'][0]['type'] === 'tool_use'
                && $body['messages'][2]['role'] === 'user' && $body['messages'][2]['content'][0]['type'] === 'tool_result'
                && $body['tools'][0]['input_schema']['type'] === 'object';
        });
    }

    public function test_provider_errors_become_model_errors(): void
    {
        Http::fake(['*' => Http::response(['error' => 'overloaded'], 529)]);
        $this->expectException(ModelError::class);
        (new AnthropicMessagesModel('k', 'm'))->respond('s', $this->transcript(), [], 100);
    }
}
