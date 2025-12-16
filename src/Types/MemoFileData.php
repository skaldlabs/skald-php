<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Data structure for creating a memo from a file upload.
 *
 * @property string|null $title Optional. The title of the memo (max 255 characters). If not provided, will be extracted from the file.
 * @property array<string, mixed>|null $metadata Optional. Custom JSON metadata
 * @property string|null $reference_id Optional. External ID mapping (max 255 characters)
 * @property string[]|null $tags Optional. Array of tags
 * @property string|null $source Optional. Source system (e.g., "notion", "confluence") (max 255 characters)
 */
final class MemoFileData
{
    /**
     * @param string|null $title
     * @param array<string, mixed>|null $metadata
     * @param string|null $reference_id
     * @param string[]|null $tags
     * @param string|null $source
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?array $metadata = null,
        public readonly ?string $reference_id = null,
        public readonly ?array $tags = null,
        public readonly ?string $source = null
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        if ($this->reference_id !== null) {
            $data['reference_id'] = $this->reference_id;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        return $data;
    }
}
