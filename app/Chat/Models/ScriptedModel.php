<?php

namespace App\Chat\Models;

use Closure;

/**
 * A stand-in model for tests and dry eval runs. Each step is either a ModelTurn, an array of transcript items, or a
 * closure(system, transcript, tools) returning one of those. It records every request it was given.
 */
class ScriptedModel implements ChatModel
{
    /** @var array<int, array{system: string, transcript: array, tools: array}> */
    public array $requests = [];

    public function __construct(private array $steps)
    {
    }

    public function name(): string
    {
        return 'scripted';
    }

    public function respond(string $system, array $transcript, array $tools, int $maxOutputTokens, bool $allowTools = true): ModelTurn
    {
        $this->requests[] = compact('system', 'transcript', 'tools', 'allowTools');
        if (!$this->steps) {
            return new ModelTurn([['type' => 'assistant', 'text' => '(no more scripted steps)']]);
        }
        $step = array_shift($this->steps);
        if ($step instanceof Closure) {
            $step = $step($system, $transcript, $tools);
        }
        return $step instanceof ModelTurn ? $step : new ModelTurn($step);
    }

    public static function say(string $text): array
    {
        return [['type' => 'assistant', 'text' => $text]];
    }

    public static function call(string $name, array $arguments = [], ?string $id = null): array
    {
        return [['type' => 'tool_call', 'id' => $id ?? 'call_' . $name . '_' . substr(md5(json_encode($arguments)), 0, 6), 'name' => $name, 'arguments' => $arguments]];
    }
}
