<?php

namespace App\Chat\Models;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI's Responses API, used statelessly (store: false): the whole transcript is sent on every call, so threads
 * live in our database and work the same with any driver. Reasoning items are replayed within a reply, as the API
 * requires around function calls.
 */
class OpenAIResponsesModel implements ChatModel
{
    public const DRIVER = 'openai';

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl = 'https://api.openai.com/v1',
        private ?string $reasoningEffort = null,
        private int $timeout = 60,
    ) {
    }

    public function name(): string
    {
        return self::DRIVER . ':' . $this->model;
    }

    public function respond(string $system, array $transcript, array $tools, int $maxOutputTokens, bool $allowTools = true): ModelTurn
    {
        $body = [
            'model' => $this->model,
            'instructions' => $system,
            'input' => $this->input($transcript),
            'max_output_tokens' => $maxOutputTokens,
            'store' => false,
        ];
        if ($tools) {
            $body['tools'] = array_map(fn($tool) => [
                'type' => 'function',
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $tool['parameters'],
                'strict' => false,
            ], $tools);
            if (!$allowTools) {
                $body['tool_choice'] = 'none';
            }
        }
        if ($this->reasoningEffort) {
            $body['reasoning'] = ['effort' => $this->reasoningEffort];
            $body['include'] = ['reasoning.encrypted_content'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeout)
                ->post(rtrim($this->baseUrl, '/') . '/responses', $body);
        } catch (ConnectionException $e) {
            throw new ModelError('Could not reach OpenAI: ' . $e->getMessage(), 0, $e);
        }
        if ($response->failed()) {
            throw new ModelError('OpenAI returned ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500));
        }

        return new ModelTurn($this->items($response->json('output') ?? []), (array) $response->json('usage'));
    }

    private function input(array $transcript): array
    {
        $input = [];
        foreach ($transcript as $item) {
            switch ($item['type']) {
                case 'user':
                    $input[] = ['role' => 'user', 'content' => $item['text']];
                    break;
                case 'assistant':
                    $input[] = ['role' => 'assistant', 'content' => $item['text']];
                    break;
                case 'tool_call':
                    $input[] = [
                        'type' => 'function_call',
                        'call_id' => $item['id'],
                        'name' => $item['name'],
                        'arguments' => json_encode((object) $item['arguments']),
                    ];
                    break;
                case 'tool_result':
                    $input[] = ['type' => 'function_call_output', 'call_id' => $item['id'], 'output' => $item['output']];
                    break;
                case 'provider':
                    if ($item['driver'] === self::DRIVER) {
                        $input[] = $item['item'];
                    }
                    break;
            }
        }
        return $input;
    }

    private function items(array $output): array
    {
        $items = [];
        foreach ($output as $entry) {
            $type = $entry['type'] ?? null;
            if ($type === 'message') {
                $text = '';
                foreach ($entry['content'] ?? [] as $part) {
                    if (($part['type'] ?? null) === 'output_text') {
                        $text .= $part['text'];
                    }
                }
                $items[] = ['type' => 'assistant', 'text' => $text];
            } elseif ($type === 'function_call') {
                $arguments = json_decode($entry['arguments'] ?? '{}', true);
                $items[] = [
                    'type' => 'tool_call',
                    'id' => $entry['call_id'],
                    'name' => $entry['name'],
                    'arguments' => is_array($arguments) ? $arguments : [],
                ];
            } elseif ($type === 'reasoning') {
                $items[] = ['type' => 'provider', 'driver' => self::DRIVER, 'item' => $entry];
            }
        }
        return $items;
    }
}
