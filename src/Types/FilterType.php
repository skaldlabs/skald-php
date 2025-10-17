<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Types of filters available.
 *
 * - native_field: Filters on built-in memo properties (title, source, client_reference_id, tags)
 * - custom_metadata: Filters on custom metadata fields
 */
enum FilterType: string
{
    case NATIVE_FIELD = 'native_field';
    case CUSTOM_METADATA = 'custom_metadata';
}
