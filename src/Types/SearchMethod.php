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
    case CHUNK_VECTOR_SEARCH = 'chunk_vector_search';

    /**
     * Case-insensitive substring match on memo titles.
     * Returns results with null distance scores.
     */
    case TITLE_CONTAINS = 'title_contains';

    /**
     * Case-insensitive prefix match on memo titles.
     * Returns results with null distance scores.
     */
    case TITLE_STARTSWITH = 'title_startswith';
}
