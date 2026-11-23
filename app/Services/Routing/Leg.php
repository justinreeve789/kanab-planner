<?php

namespace App\Services\Routing;

/**
 * One drive: how long, how far, where the answer came from, and anything on the road to know about.
 */
final class Leg
{
    public const SOURCE_ROUTER = 'router';
    public const SOURCE_ESTIMATE = 'estimate';

    /**
     * @param Point[] $shape the route's path, when the router returned one
     * @param array<int, array{type: string, text: string, closure: bool}> $notes
     */
    public function __construct(
        public readonly int $minutes,
        public readonly float $miles,
        public readonly string $source,
        public readonly array $shape = [],
        public readonly array $notes = [],
    ) {
    }

    public static function none(): self
    {
        return new self(0, 0.0, self::SOURCE_ESTIMATE);
    }

    public function withDelay(int $minutes, array $note): self
    {
        return new self($this->minutes + $minutes, $this->miles, $this->source, $this->shape, [...$this->notes, $note]);
    }

    public function withNote(array $note): self
    {
        return new self($this->minutes, $this->miles, $this->source, $this->shape, [...$this->notes, $note]);
    }

    /** For the cache: everything but the shape, which is only needed while checking road events. */
    public function toArray(): array
    {
        return ['minutes' => $this->minutes, 'miles' => $this->miles, 'source' => $this->source, 'notes' => $this->notes];
    }
}
