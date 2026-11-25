<?php

namespace App\Chat;

/**
 * What of a thread's stored transcript is replayed to the model: the last N user/assistant exchanges, whole (a tool
 * call is never separated from its result), with tool results from older exchanges shortened.
 */
final class History
{
    public const OLD_RESULT_CHARS = 600;

    public static function forModel(array $stored): array
    {
        $exchanges = self::exchanges($stored);
        $keep = array_slice($exchanges, -max(1, (int) config('chat.history_messages') / 2));
        $lastIndex = count($keep) - 1;

        $items = [];
        foreach ($keep as $i => $exchange) {
            foreach ($exchange as $item) {
                if ($item['type'] === 'tool_result' && $i < $lastIndex && mb_strlen($item['output']) > self::OLD_RESULT_CHARS) {
                    $item['output'] = mb_substr($item['output'], 0, self::OLD_RESULT_CHARS) . '... [shortened; call the tool again for details]';
                }
                $items[] = $item;
            }
        }
        return self::paired($items);
    }

    /** Stored transcript, trimmed to a size worth keeping in the database. */
    public static function forStorage(array $stored, int $maxExchanges = 40): array
    {
        return array_merge(...array_slice(self::exchanges($stored), -$maxExchanges) ?: [[]]);
    }

    /** Split into exchanges, each starting with a user item. */
    private static function exchanges(array $items): array
    {
        $exchanges = [];
        foreach ($items as $item) {
            if (!in_array($item['type'] ?? null, ['user', 'assistant', 'tool_call', 'tool_result'], true)) {
                continue;
            }
            if ($item['type'] === 'user' || !$exchanges) {
                $exchanges[] = [];
            }
            $exchanges[array_key_last($exchanges)][] = $item;
        }
        return $exchanges;
    }

    /** Drop tool calls without results and results without calls, which providers reject. */
    private static function paired(array $items): array
    {
        $calls = [];
        $results = [];
        foreach ($items as $item) {
            if ($item['type'] === 'tool_call') {
                $calls[$item['id']] = true;
            } elseif ($item['type'] === 'tool_result') {
                $results[$item['id']] = true;
            }
        }
        return array_values(array_filter($items, fn($item) => match ($item['type']) {
            'tool_call' => isset($results[$item['id']]),
            'tool_result' => isset($calls[$item['id']]),
            default => true,
        }));
    }
}
