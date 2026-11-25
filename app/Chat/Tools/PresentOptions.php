<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;

class PresentOptions extends BaseTool
{
    public const COMPONENT = 'choices';

    public function name(): string
    {
        return 'present_options';
    }

    public function description(): string
    {
        return 'Ask the traveler to pick one option, shown as buttons; ends your turn. Use when a name matches several '
            . 'different records ("which Overlook trail?"), or when one short question would change the answer. Each '
            . 'option needs a label they will recognize (name and area) and a short detail (distance, what it is). The '
            . 'reply they send is the option\'s "reply" text.';
    }

    public function parameters(): array
    {
        return self::object([
            'question' => ['type' => 'string'],
            'options' => [
                'type' => 'array',
                'minItems' => 2,
                'maxItems' => 6,
                'items' => self::object([
                    'label' => ['type' => 'string'],
                    'detail' => ['type' => 'string'],
                    'reply' => ['type' => 'string', 'description' => 'Sent back as the traveler\'s message, e.g. "Canyon Overlook (Zion), trail 377".'],
                    'record' => self::object([
                        'table' => ['type' => 'string', 'enum' => ['trails', 'restaurants', 'locations', 'events', 'accommodations']],
                        'id' => ['type' => 'integer'],
                    ]),
                ], ['label']),
            ],
        ], ['question', 'options']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'question' => 'required|string|max:300',
            'options' => 'required|array|min:2|max:6',
            'options.*.label' => 'required|string|max:120',
            'options.*.detail' => 'nullable|string|max:200',
            'options.*.reply' => 'nullable|string|max:200',
            'options.*.record' => 'nullable|array',
            'options.*.record.table' => 'nullable|in:trails,restaurants,locations,events,accommodations',
            'options.*.record.id' => 'nullable|integer',
        ]);
        $options = array_map(fn($option) => [
            'label' => $option['label'],
            'detail' => $option['detail'] ?? null,
            'reply' => $option['reply'] ?? $option['label'],
            'record' => $option['record'] ?? null,
        ], $args['options']);

        return new ToolResult(
            ['shown' => true, 'note' => 'The options are on screen. End your turn now; at most one short sentence.'],
            [],
            self::COMPONENT,
            ['question' => $args['question'], 'options' => $options],
            endsTurn: true,
        );
    }
}
