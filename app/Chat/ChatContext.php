<?php

namespace App\Chat;

use App\Models\Itinerary\Itinerary;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Everything a reply may depend on besides the message itself: the time, who is asking, their trip and what they're
 * looking at. Tools read it; only EditItinerary acts on canEdit.
 */
final class ChatContext
{
    public function __construct(
        public readonly CarbonImmutable $now,
        public readonly ?User $user = null,
        public readonly ?Itinerary $itinerary = null,
        public readonly bool $canEditItinerary = false,
        public readonly ?string $itineraryToken = null,
        /** ['table' => 'trails', 'id' => 12] when the traveler opened the chat from a record. */
        public readonly ?array $focus = null,
        /** Earlier transcript items for this thread (see ChatModel). */
        public readonly array $history = [],
    ) {
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('chat.timezone'));
    }

    public function withHistory(array $history): self
    {
        return new self($this->now, $this->user, $this->itinerary, $this->canEditItinerary, $this->itineraryToken, $this->focus, $history);
    }

    public function today(): string
    {
        return $this->now->toDateString();
    }
}
