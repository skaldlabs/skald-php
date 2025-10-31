<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Available search methods for querying memos.
 */
enum SearchMethod: string
{
    /**
     * Semantic search on memo chunks for detailed content search.
     * Returns results with distance scores (0-2, closer to 0 = more relevant).
     */
    case CHUNK_SEMANTIC_SEARCH = 'chunk_semantic_search';
}
