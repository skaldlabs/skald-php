<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Request parameters for document generation.
 *
 * @property string $prompt Required. Prompt describing document to generate
 * @property string|null $rules Optional. Style/format rules
 * @property string|null $project_id Optional. Project UUID (required with Token Authentication)
 */
final class GenerateDocRequest
{
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $rules = null,
        public readonly ?string $project_id = null
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

        if ($this->project_id !== null) {
            $data['project_id'] = $this->project_id;
        }

        return $data;
    }
}
