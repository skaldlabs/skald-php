<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Filter for narrowing search and chat results.
 *
 * Filters enable targeting specific memos by source, tags, or custom metadata.
 * Multiple filters use AND logic - all conditions must match.
 *
 * @property string $field The field to filter on
 * @property FilterOperator $operator The comparison operator
 * @property mixed $value The value to compare against
 * @property FilterType $filter_type Whether this is a native field or custom metadata filter
 */
final class Filter
{
    /**
     * @param string $field
     * @param FilterOperator $operator
     * @param mixed $value
     * @param FilterType $filterType
     */
    public function __construct(
        public readonly string $field,
        public readonly FilterOperator $operator,
        public readonly mixed $value,
        public readonly FilterType $filterType
    ) {
    }

    /**
     * Create a filter for a native field.
     *
     * Native fields: title, source, client_reference_id, tags
     *
     * @param string $field
     * @param FilterOperator $operator
     * @param mixed $value
     * @return self
     */
    public static function nativeField(string $field, FilterOperator $operator, mixed $value): self
    {
        return new self($field, $operator, $value, FilterType::NATIVE_FIELD);
    }

    /**
     * Create a filter for custom metadata.
     *
     * @param string $field The metadata field name
     * @param FilterOperator $operator
     * @param mixed $value
     * @return self
     */
    public static function customMetadata(string $field, FilterOperator $operator, mixed $value): self
    {
        return new self($field, $operator, $value, FilterType::CUSTOM_METADATA);
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator->value,
            'value' => $this->value,
            'filter_type' => $this->filterType->value,
        ];
    }
}
