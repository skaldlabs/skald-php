<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Request parameters for document generation.
 *
 * @property string $prompt Required. Prompt describing document to generate
 * @property string|null $rules Optional. Style/format rules
 * @property Filter[]|null $filters Optional. Array of filters to narrow source memos
 */
final class GenerateDocRequest
{
    /**
     * @param string $prompt
     * @param string|null $rules
     * @param Filter[]|null $filters
     */
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $rules = null,
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
            'prompt' => $this->prompt,
            'stream' => $stream,
        ];

        if ($this->rules !== null) {
            $data['rules'] = $this->rules;
        }

        if ($this->filters !== null) {
            $data['filters'] = array_map(fn($filter) => $filter->toArray(), $this->filters);
        }

        return $data;
    }
}
