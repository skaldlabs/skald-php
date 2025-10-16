<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Individual search result.
 *
 * @property string $uuid Unique identifier for the memo
 * @property string $title Memo title
 * @property string $summary Auto-generated summary
 * @property string $content_snippet Snippet containing beginning of memo
 * @property float|null $distance Relevance score (0-2 for chunk_vector_search, null for title searches)
 */
final class SearchResult
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $content_snippet,
        public readonly ?float $distance
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
            $data['uuid'],
            $data['title'],
            $data['summary'],
            $data['content_snippet'],
            $data['distance']
        );
    }
}
