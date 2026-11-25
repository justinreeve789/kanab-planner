<?php

namespace App\Chat\Models;

/**
 * A language model that can call tools. Drivers translate the provider-neutral transcript to their own API.
 *
 * Transcript items (arrays):
 *  - ['type' => 'user', 'text' => string]
 *  - ['type' => 'assistant', 'text' => string]
 *  - ['type' => 'tool_call', 'id' => string, 'name' => string, 'arguments' => array]
 *  - ['type' => 'tool_result', 'id' => string, 'name' => string, 'output' => string]
 *  - ['type' => 'provider', 'driver' => string, 'item' => array]  (opaque items a driver must replay, e.g. reasoning)
 *
 * Tools: ['name' => string, 'description' => string, 'parameters' => JSON schema array].
 */
interface ChatModel
{
    /**
     * $allowTools false keeps the tool definitions (providers require them once the transcript has tool calls) but
     * forbids calling them, so the model must answer in text.
     *
     * @throws ModelError when the provider fails or returns something unusable
     */
    public function respond(string $system, array $transcript, array $tools, int $maxOutputTokens, bool $allowTools = true): ModelTurn;

    /** Provider and model, for logs and eval reports, e.g. "openai:gpt-4.1-mini". */
    public function name(): string;
}
