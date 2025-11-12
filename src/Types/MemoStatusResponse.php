<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Response from checking memo processing status.
 *
 * @property string $memo_uuid The UUID of the memo
 * @property string $status Processing status: 'processing', 'processed', or 'error'
 * @property string|null $error_reason Reason for error if status is 'error'
 */
final class MemoStatusResponse
{
    public function __construct(
        public readonly string $memo_uuid,
        public readonly string $status,
        public readonly ?string $error_reason = null
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
            $data['memo_uuid'] ?? '',
            $data['status'] ?? 'unknown',
            $data['error_reason'] ?? null
        );
    }

    /**
     * Check if memo is still processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if memo has been processed.
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if memo processing failed.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
