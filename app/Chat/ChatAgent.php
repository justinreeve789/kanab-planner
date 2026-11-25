<?php

namespace App\Chat;

use App\Chat\Models\ChatModel;
use App\Chat\Models\ModelError;
use App\Chat\Tools\ToolRegistry;
use App\Chat\Tools\ToolResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The reply loop: send the conversation and tool definitions to the model, run the tools it asks for, send the
 * results back, and repeat until it answers in text (or a guard trips).
 */
class ChatAgent
{
    public function __construct(
        private ChatModel $model,
        private ToolRegistry $tools,
        private SystemPrompt $prompt,
    ) {
    }

    public function model(): ChatModel
    {
        return $this->model;
    }

    public function reply(ChatContext $context, string $message): ChatReply
    {
        $system = $this->prompt->build($context);
        $definitions = $this->tools->definitions();
        $maxSteps = max(1, (int) config('chat.max_steps'));
        $maxTokens = (int) config('chat.max_output_tokens');
        $maxChars = (int) config('chat.tool_result_max_chars');
        $deadline = microtime(true) + (int) config('chat.timeout_seconds');

        $transcript = [...History::forModel($context->history), ['type' => 'user', 'text' => $message]];
        $newItems = [['type' => 'user', 'text' => $message]];
        $sources = [];
        $trace = [];
        $usage = [];
        $component = null;
        $componentParams = [];
        $text = '';

        try {
            for ($step = 1; $step <= $maxSteps + 1; $step++) {
                // Out of steps or time: one last call without tools, so the model must answer with what it has.
                $final = $step > $maxSteps || microtime(true) > $deadline;
                $turn = $this->model->respond($system, $transcript, $definitions, $maxTokens, !$final);
                $usage = self::addUsage($usage, $turn->usage);
                $transcript = [...$transcript, ...$turn->items];
                $newItems = [...$newItems, ...array_filter($turn->items, fn($item) => $item['type'] !== 'provider')];

                $calls = $final ? [] : $turn->toolCalls();
                if (!$calls) {
                    $text = $turn->text();
                    break;
                }

                $endsTurn = false;
                foreach ($calls as $call) {
                    [$result, $entry] = $this->run($call, $context);
                    $trace[] = $entry;
                    $output = ['type' => 'tool_result', 'id' => $call['id'], 'name' => $call['name'], 'output' => $result->toModelOutput($maxChars)];
                    $transcript[] = $output;
                    $newItems[] = $output;
                    $sources = [...$sources, ...$result->sources];
                    if ($result->component) {
                        $component = $result->component;
                        $componentParams = $result->componentParams;
                    }
                    $endsTurn = $endsTurn || $result->endsTurn;
                }

                // present_options asks the traveler something: stop and wait for their pick.
                if ($endsTurn) {
                    $text = $turn->text() ?: (string) ($componentParams['question'] ?? '');
                    break;
                }
            }
        } catch (ModelError $e) {
            Log::warning('Chat model failed', ['model' => $this->model->name(), 'error' => $e->getMessage()]);
            return new ChatReply(
                "Sorry, I couldn't get an answer just now. Please try again in a moment.",
                [], null, [], $trace, [['type' => 'user', 'text' => $message]], $usage, true,
            );
        }

        if ($text !== '') {
            $last = end($newItems);
            if (!$last || $last['type'] !== 'assistant' || $last['text'] !== $text) {
                $newItems[] = ['type' => 'assistant', 'text' => $text];
            }
        }

        return new ChatReply($text, self::uniqueSources($sources), $component, $componentParams, $trace, $newItems, $usage);
    }

    /** @return array{0: ToolResult, 1: array} */
    private function run(array $call, ChatContext $context): array
    {
        $started = microtime(true);
        $ok = true;
        try {
            if (!$this->tools->has($call['name'])) {
                $result = ToolResult::unavailable("There is no tool named {$call['name']}.");
                $ok = false;
            } else {
                $result = $this->tools->get($call['name'])->handle($call['arguments'], $context);
                $ok = empty($result->data['unavailable']) && empty($result->data['error']);
            }
        } catch (ValidationException $e) {
            $result = new ToolResult(['error' => 'Invalid arguments', 'details' => $e->errors()]);
            $ok = false;
        } catch (Throwable $e) {
            Log::error('Chat tool failed', ['tool' => $call['name'], 'arguments' => $call['arguments'], 'error' => (string) $e]);
            $result = new ToolResult(['error' => 'This lookup failed unexpectedly. Say you could not check it.']);
            $ok = false;
        }
        return [$result, [
            'name' => $call['name'],
            'arguments' => $call['arguments'],
            'ok' => $ok,
            'ms' => (int) round((microtime(true) - $started) * 1000),
        ]];
    }

    private static function uniqueSources(array $sources): array
    {
        $seen = [];
        $unique = [];
        foreach ($sources as $source) {
            $key = ($source['url'] ?? '') . '|' . ($source['title'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $source;
            }
        }
        return $unique;
    }

    private static function addUsage(array $total, array $usage): array
    {
        foreach ($usage as $key => $value) {
            if (is_int($value)) {
                $total[$key] = ($total[$key] ?? 0) + $value;
            }
        }
        return $total;
    }
}
