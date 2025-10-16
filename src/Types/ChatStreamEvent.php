<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Stream event from chat operations.
 *
 * @property string $type Event type: 'token' or 'done'
 * @property string|null $content Text token when type is 'token'
 */
final class ChatStreamEvent
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $content = null
    ) {
    }

    /**
     * Create from API event array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'],
            $data['content'] ?? null
        );
    }

    /**
     * Check if this is a token event.
     *
     * @return bool
     */
    public function isToken(): bool
    {
        return $this->type === 'token';
    }

    /**
     * Check if this is a done event.
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->type === 'done';
    }
}
