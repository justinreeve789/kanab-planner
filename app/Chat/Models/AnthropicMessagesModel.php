<?php

namespace App\Chat\Models;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Anthropic's Messages API. Consecutive transcript items are merged into alternating user/assistant messages, with
 * tool results sent back as user content.
 */
class AnthropicMessagesModel implements ChatModel
{
    public const DRIVER = 'anthropic';

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl = 'https://api.anthropic.com/v1',
        private string $version = '2023-06-01',
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
            'system' => $system,
            'max_tokens' => $maxOutputTokens,
            'messages' => $this->messages($transcript),
        ];
        if ($tools) {
            $body['tools'] = array_map(fn($tool) => [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'input_schema' => $tool['parameters'],
            ], $tools);
            if (!$allowTools) {
                $body['tool_choice'] = ['type' => 'none'];
            }
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
            ])->acceptJson()->timeout($this->timeout)->post(rtrim($this->baseUrl, '/') . '/messages', $body);
        } catch (ConnectionException $e) {
            throw new ModelError('Could not reach Anthropic: ' . $e->getMessage(), 0, $e);
        }
        if ($response->failed()) {
            throw new ModelError('Anthropic returned ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500));
        }

        $items = [];
        foreach ($response->json('content') ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $items[] = ['type' => 'assistant', 'text' => $block['text']];
            } elseif (($block['type'] ?? null) === 'tool_use') {
                $items[] = [
                    'type' => 'tool_call',
                    'id' => $block['id'],
                    'name' => $block['name'],
                    'arguments' => is_array($block['input'] ?? null) ? $block['input'] : [],
                ];
            }
        }
        return new ModelTurn($items, (array) $response->json('usage'));
    }

    private function messages(array $transcript): array
    {
        $messages = [];
        $push = function(string $role, array $block) use (&$messages) {
            $last = array_key_last($messages);
            if ($last !== null && $messages[$last]['role'] === $role) {
                $messages[$last]['content'][] = $block;
            } else {
                $messages[] = ['role' => $role, 'content' => [$block]];
            }
        };

        foreach ($transcript as $item) {
            match ($item['type']) {
                'user' => $push('user', ['type' => 'text', 'text' => $item['text']]),
                'assistant' => trim($item['text']) === '' ? null : $push('assistant', ['type' => 'text', 'text' => $item['text']]),
                'tool_call' => $push('assistant', [
                    'type' => 'tool_use',
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'input' => (object) $item['arguments'],
                ]),
                'tool_result' => $push('user', [
                    'type' => 'tool_result',
                    'tool_use_id' => $item['id'],
                    'content' => $item['output'],
                ]),
                default => null,
            };
        }

        // The API requires the conversation to start with a user turn.
        if ($messages && $messages[0]['role'] !== 'user') {
            array_unshift($messages, ['role' => 'user', 'content' => [['type' => 'text', 'text' => '(continuing)']]]);
        }
        return $messages;
    }
}
