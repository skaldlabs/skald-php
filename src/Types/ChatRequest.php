<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Request parameters for chat operations.
 *
 * @property string $query Required. The question to ask
 * @property string|null $project_id Optional. Project UUID (required with Token Authentication)
 */
final class ChatRequest
{
    public function __construct(
        public readonly string $query,
        public readonly ?string $project_id = null
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @param bool $stream Whether to enable streaming
     * @return array<string, mixed>
     */
    public function toArray(bool $stream = false): array
    {
        $data = [
            'query' => $this->query,
            'stream' => $stream,
        ];

        if ($this->project_id !== null) {
            $data['project_id'] = $this->project_id;
        }

        return $data;
    }
}
