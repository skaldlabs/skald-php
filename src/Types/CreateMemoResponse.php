<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Response from creating a memo.
 *
 * @property string $memo_uuid The UUID of the created memo
 */
final class CreateMemoResponse
{
    public function __construct(
        public readonly string $memo_uuid
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
        return new self($data['memo_uuid']);
    }
}
