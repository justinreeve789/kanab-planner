<?php

namespace App\Chat\Models;

/**
 * One model response: the transcript items it produced, in order (assistant text, tool calls, provider items).
 */
final class ModelTurn
{
    public function __construct(
        public readonly array $items,
        public readonly array $usage = [],
    ) {
    }

    public function text(): string
    {
        return trim(implode("\n\n", array_map(
            fn($item) => $item['text'],
            array_filter($this->items, fn($item) => $item['type'] === 'assistant' && trim($item['text']) !== '')
        )));
    }

    /** @return array<int, array{id: string, name: string, arguments: array}> */
    public function toolCalls(): array
    {
        return array_values(array_filter($this->items, fn($item) => $item['type'] === 'tool_call'));
    }
}
