<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;

interface Tool
{
    /** snake_case name the model calls. */
    public function name(): string;

    /** What it's for and when to use it, written for the model. */
    public function description(): string;

    /** JSON schema of the arguments (type: object). */
    public function parameters(): array;

    public function handle(array $args, ChatContext $context): ToolResult;
}
