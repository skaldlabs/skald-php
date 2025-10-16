<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Response from a document generation operation.
 *
 * @property bool $ok Success status
 * @property string $response Generated document with inline citations [[N]]
 * @property array<mixed> $intermediate_steps Steps taken by the agent (for debugging)
 */
final class GenerateDocResponse
{
    /**
     * @param bool $ok
     * @param string $response
     * @param array<mixed> $intermediate_steps
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $response,
        public readonly array $intermediate_steps
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
        return new self(
            $data['ok'] ?? false,
            $data['response'] ?? '',
            $data['intermediate_steps'] ?? []
        );
    }
}
