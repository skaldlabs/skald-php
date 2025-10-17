<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Operators available for filtering.
 *
 * - eq: Exact match
 * - neq: Not equal
 * - contains: Substring match (case-insensitive)
 * - startswith: Prefix match (case-sensitive)
 * - endswith: Suffix match (case-sensitive)
 * - in: Value within array
 * - not_in: Value outside array
 */
enum FilterOperator: string
{
    case EQ = 'eq';
    case NEQ = 'neq';
    case CONTAINS = 'contains';
    case STARTSWITH = 'startswith';
    case ENDSWITH = 'endswith';
    case IN = 'in';
    case NOT_IN = 'not_in';
}
