<?php

declare(strict_types=1);

namespace Skald\Types;

/**
 * Data structure for updating an existing memo.
 *
 * All fields are optional. When content is updated, the memo is automatically
 * reprocessed (summary, tags, and chunks are regenerated).
 *
 * @property string|null $title Optional. The title of the memo (max 255 characters)
 * @property string|null $content Optional. The content of the memo
 * @property array<string, mixed>|null $metadata Optional. Custom JSON metadata
 * @property string|null $client_reference_id Optional. External ID mapping (max 255 characters)
 * @property string|null $source Optional. Source system (e.g., "notion", "confluence") (max 255 characters)
 * @property string|null $expiration_date Optional. Expiration date in datetime format
 */
final class UpdateMemoData
{
    /**
     * @param string|null $title
     * @param string|null $content
     * @param array<string, mixed>|null $metadata
     * @param string|null $client_reference_id
     * @param string|null $source
     * @param string|null $expiration_date
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $content = null,
        public readonly ?array $metadata = null,
        public readonly ?string $client_reference_id = null,
        public readonly ?string $source = null,
        public readonly ?string $expiration_date = null
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

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        if ($this->client_reference_id !== null) {
            $data['client_reference_id'] = $this->client_reference_id;
        }

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        if ($this->expiration_date !== null) {
            $data['expiration_date'] = $this->expiration_date;
        }

        return $data;
    }
}
