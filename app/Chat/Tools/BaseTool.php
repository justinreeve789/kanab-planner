<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Models\Trail;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Helpers shared by tools: argument validation, loading records, dates, and the planner's own record links.
 */
abstract class BaseTool implements Tool
{
    /** Validate $args with Laravel rules; a ValidationException goes back to the model as "Invalid arguments". */
    protected function validate(array $args, array $rules): array
    {
        return Validator::make($args, $rules)->validate();
    }

    protected function trail(int $id): ?Trail
    {
        return Trail::with('area')->where('is_usable', 1)->find($id);
    }

    protected function date(?string $value, ChatContext $context): CarbonImmutable
    {
        if (!$value) {
            return $context->now;
        }
        return CarbonImmutable::parse($value, config('chat.timezone'));
    }

    /** A trail record as a citable source. */
    protected function trailSource(Trail $trail): array
    {
        return ToolResult::source(html_entity_decode($trail->name, ENT_QUOTES) . ' (our trail record)', $trail->url ?: null, ToolResult::OUR_DATA);
    }

    protected static function clip(?string $text, int $chars = 400): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }
        return Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES))), $chars);
    }

    protected static function object(array $properties, array $required = []): array
    {
        $schema = ['type' => 'object', 'properties' => $properties ?: new \stdClass()];
        if ($required) {
            $schema['required'] = $required;
        }
        return $schema;
    }

    protected function notFound(string $what, int|string $id): ToolResult
    {
        return ToolResult::unavailable("No usable {$what} with id {$id}. Use a search tool to find the right id.");
    }
}
