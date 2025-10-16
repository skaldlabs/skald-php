<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Response from creating a memo.
 *
 * @property bool $ok Always true on success
 */
final class CreateMemoResponse
{
    public function __construct(
        public readonly bool $ok
    ) {
    }

    /**
     * Create from API response array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data['ok'] ?? false);
    }
}
