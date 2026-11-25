<?php

namespace App\Chat\Tools;

/**
 * What a tool hands back: data for the model, the sources behind it (shown to the traveler) and, optionally, a UI
 * component for the reply (e.g. choice buttons).
 *
 * Source: ['title' => string, 'url' => ?string, 'kind' => our_data|official|live|blog|review, 'as_of' => ?string]
 */
final class ToolResult
{
    public const OUR_DATA = 'our_data';
    public const OFFICIAL = 'official';
    public const LIVE = 'live';
    public const BLOG = 'blog';
    public const REVIEW = 'review';

    public function __construct(
        public readonly array $data,
        public readonly array $sources = [],
        public readonly ?string $component = null,
        public readonly array $componentParams = [],
        public readonly bool $endsTurn = false,
    ) {
    }

    public static function ok(array $data, array $sources = []): self
    {
        return new self($data, $sources);
    }

    /**
     * A failure the model should explain, not hide: e.g. a feed is down or a record doesn't exist. $fallbackUrl is the
     * official page the traveler can check instead.
     */
    public static function unavailable(string $reason, ?string $fallbackUrl = null, array $extra = []): self
    {
        return new self(['unavailable' => true, 'reason' => $reason, 'check_instead' => $fallbackUrl] + $extra);
    }

    public static function source(string $title, ?string $url, string $kind, ?string $asOf = null): array
    {
        return array_filter(['title' => $title, 'url' => $url, 'kind' => $kind, 'as_of' => $asOf], fn($v) => $v !== null);
    }

    /** JSON for the model, cut to $maxChars so one big result can't crowd out the conversation. */
    public function toModelOutput(int $maxChars): string
    {
        $json = json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (mb_strlen($json) <= $maxChars) {
            return $json;
        }
        return mb_substr($json, 0, $maxChars) . '... [truncated]';
    }
}
