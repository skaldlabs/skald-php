<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Request parameters for searching memos.
 *
 * @property string $query Required. The search query
 * @property SearchMethod $search_method Required. The search method to use
 * @property int|null $limit Optional. Results limit (1-50, default 10)
 * @property Filter[]|null $filters Optional. Array of filters to narrow results
 */
final class SearchRequest
{
    /**
     * @param string $query
     * @param SearchMethod $searchMethod
     * @param int|null $limit
     * @param Filter[]|null $filters
     */
    public function __construct(
        public readonly string $query,
        public readonly SearchMethod $searchMethod,
        public readonly ?int $limit = null,
        public readonly ?array $filters = null
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'query' => $this->query,
            'search_method' => $this->searchMethod->value,
        ];

        if ($this->limit !== null) {
            $data['limit'] = $this->limit;
        }

        if ($this->filters !== null) {
            $data['filters'] = array_map(fn($filter) => $filter->toArray(), $this->filters);
        }

        return $data;
    }
}
