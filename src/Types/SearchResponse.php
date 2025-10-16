<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Response from a search operation.
 *
 * @property SearchResult[] $results Array of search results
 */
final class SearchResponse
{
    /**
     * @param SearchResult[] $results
     */
    public function __construct(
        public readonly array $results
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
        $results = array_map(
            fn(array $result) => SearchResult::fromArray($result),
            $data['results'] ?? []
        );

        return new self($results);
    }
}
