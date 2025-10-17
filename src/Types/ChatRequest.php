<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Request parameters for chat operations.
 *
 * @property string $query Required. The question to ask
 * @property Filter[]|null $filters Optional. Array of filters to narrow context
 */
final class ChatRequest
{
    /**
     * @param string $query
     * @param Filter[]|null $filters
     */
    public function __construct(
        public readonly string $query,
        public readonly ?array $filters = null
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


        if ($this->filters !== null) {
            $data['filters'] = array_map(fn($filter) => $filter->toArray(), $this->filters);
        }

        return $data;
    }
}
