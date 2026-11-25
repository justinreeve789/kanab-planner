<?php

namespace App\Chat;

final class ChatReply
{
    public function __construct(
        public readonly string $text,
        /** Deduplicated sources behind the answer, in the order they were used. */
        public readonly array $sources,
        public readonly ?string $component,
        public readonly array $componentParams,
        /** [['name', 'arguments', 'ok', 'ms']] for logs and evals. */
        public readonly array $toolTrace,
        /** New transcript items to store on the thread (user message onward, provider items excluded). */
        public readonly array $transcript,
        public readonly array $usage = [],
        public readonly bool $failed = false,
    ) {
    }

    public function toolsUsed(): array
    {
        return array_values(array_unique(array_column($this->toolTrace, 'name')));
    }
}
