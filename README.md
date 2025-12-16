# Skald PHP SDK

Official PHP SDK for [Skald API](https://useskald.com) - A knowledge base management system that automatically processes memos (summarizes, chunks, and indexes them) and provides semantic search, AI chat, and document generation capabilities.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)

## Features

- **Memo Management**: Create and store memos with automatic processing (summarization, chunking, indexing)
- **File Upload**: Upload documents (PDF, DOC, DOCX, PPTX up to 100MB) for automatic processing
- **Status Tracking**: Check memo processing status to know when documents are ready
- **Semantic Search**: Search through your knowledge base using vector search or title matching
- **AI Chat**: Ask questions about your knowledge base with AI-powered responses and inline citations
- **Document Generation**: Generate documents based on prompts with context from your knowledge base
- **Streaming Support**: Real-time streaming for chat and document generation operations
- **Type-Safe**: Fully typed with PHP 8.1+ features including enums and readonly properties

## Requirements

- PHP 8.1 or higher
- cURL extension
- JSON extension

## Installation

Install via Composer:

```bash
composer require skald/skald-php
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Skald\Skald;
use Skald\Types\MemoData;
use Skald\Types\SearchRequest;
use Skald\Types\SearchMethod;
use Skald\Types\ChatRequest;

// Initialize the client
$skald = new Skald('sk_proj_your_api_key');

// Create a memo
$result = $skald->createMemo(new MemoData(
    title: 'Meeting Notes - Q1 Planning',
    content: 'Discussed quarterly goals, hiring plans, and budget allocation...',
    tags: ['meeting', 'planning', 'q1'],
    source: 'notion'
));

// Search for memos
$results = $skald->search(new SearchRequest(
    query: 'quarterly goals',
    searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
    limit: 10
));

foreach ($results->results as $result) {
    echo "Title: {$result->title}\n";
    echo "Summary: {$result->summary}\n";
    echo "Relevance: {$result->distance}\n\n";
}

// Ask questions about your knowledge base
$response = $skald->chat(new ChatRequest(
    query: 'What are our main goals for Q1?'
));

echo $response->response; // "The main Q1 goals are... [[1]]"
```

## API Reference

### Client Initialization

```php
$skald = new Skald(string $apiKey, ?string $baseUrl = null);
```

- `$apiKey`: Your Skald API key (required)
- `$baseUrl`: Optional custom API base URL (defaults to `https://api.useskald.com`)

### Creating Memos

```php
$response = $skald->createMemo(MemoData $memoData): CreateMemoResponse;
```

**Parameters:**

```php
new MemoData(
    title: string,              // Required - memo title (max 255 chars)
    content: string,            // Required - memo content
    metadata: ?array = null,    // Optional - custom JSON metadata
    reference_id: ?string = null, // Optional - external ID mapping
    tags: ?array = null,        // Optional - array of tags
    source: ?string = null      // Optional - source system (e.g., "notion")
);
```

**Example:**

```php
$result = $skald->createMemo(new MemoData(
    title: 'Product Requirements',
    content: 'The new mobile app should support offline mode...',
    metadata: ['author' => 'John Doe', 'version' => '1.0'],
    tags: ['product', 'mobile', 'requirements'],
    source: 'confluence'
));

// Returns: CreateMemoResponse { ok: true }
```

### Uploading Files

```php
$response = $skald->createMemoFromFile(
    string $filePath,
    ?MemoFileData $memoData = null
): CreateMemoResponse;
```

Upload a document file (PDF, DOC, DOCX, PPTX, up to 100MB) that will be automatically processed by the Skald API.

**Parameters:**

- `$filePath` (string): Path to the file to upload
- `$memoData` (MemoFileData|null, optional): Optional metadata for the memo

```php
new MemoFileData(
    title: ?string = null,           // Optional - memo title (extracted from file if not provided)
    metadata: ?array = null,         // Optional - custom JSON metadata
    reference_id: ?string = null,    // Optional - external ID mapping
    tags: ?array = null,            // Optional - array of tags
    source: ?string = null          // Optional - source system (e.g., "google-drive")
);
```

**Example:**

```php
use Skald\Types\MemoFileData;

// Upload with metadata
$result = $skald->createMemoFromFile(
    '/path/to/document.pdf',
    new MemoFileData(
        title: 'Q1 Planning Document',
        metadata: ['department' => 'Engineering'],
        tags: ['planning', 'quarterly'],
        source: 'google-drive',
        reference_id: 'gdrive-doc-12345'
    )
);

// Upload without metadata (title will be extracted from file)
$result = $skald->createMemoFromFile('/path/to/document.pdf');

// Returns: CreateMemoResponse { ok: true }
```

### Checking Memo Status

```php
$response = $skald->checkMemoStatus(
    string $memoId,
    string $idType = 'memo_uuid'
): MemoStatusResponse;
```

Check the processing status of a memo to know when it's ready for search and chat.

**Parameters:**

- `$memoId` (string): The memo UUID or client reference ID
- `$idType` (string, optional): Type of identifier - `'memo_uuid'` (default) or `'reference_id'`

**Status Values:**

- `processing` - Memo is being processed
- `processed` - Memo is ready for search and chat
- `error` - Processing failed

**Example:**

```php
// Check status by memo UUID
$status = $skald->checkMemoStatus('memo-uuid-here');

echo "Status: {$status->status}\n";

if ($status->isProcessing()) {
    echo "Still processing...\n";
} elseif ($status->isProcessed()) {
    echo "Ready for search and chat!\n";
} elseif ($status->isError()) {
    echo "Processing failed: {$status->error_reason}\n";
}

// Check status by reference ID
$status = $skald->checkMemoStatus('external-ref-123', 'reference_id');

// Poll until processing is complete
$maxAttempts = 30;
$attempt = 0;

while ($attempt < $maxAttempts) {
    $status = $skald->checkMemoStatus('memo-uuid-here');

    if ($status->isProcessed()) {
        echo "Processing complete!\n";
        break;
    } elseif ($status->isError()) {
        echo "Processing failed!\n";
        break;
    }

    sleep(2); // Wait 2 seconds before checking again
    $attempt++;
}
```

### Updating Memos

```php
$response = $skald->updateMemo(
    string $memoId,
    UpdateMemoData $updateData,
    string $idType = 'memo_uuid',
    ?string $projectId = null
): CreateMemoResponse;
```

Update an existing memo with partial or complete changes. All fields are optional - only include the fields you want to update.

**Important**: When `content` is updated, the memo is automatically reprocessed by the API (summary, tags, and chunks are regenerated). Other field updates preserve existing processing results.

**Parameters:**

- `$memoId` (string): The memo UUID or client reference ID
- `$updateData` (UpdateMemoData): The fields to update
- `$idType` (string, optional): Type of identifier - `'memo_uuid'` (default) or `'reference_id'`
- `$projectId` (string|null, optional): Project UUID (required when using Token Authentication)

```php
new UpdateMemoData(
    title: ?string = null,                  // Optional - memo title (max 255 chars)
    content: ?string = null,                // Optional - memo content (triggers reprocessing)
    metadata: ?array = null,                // Optional - custom JSON metadata
    client_reference_id: ?string = null,    // Optional - external ID mapping (max 255 chars)
    source: ?string = null,                 // Optional - source system (max 255 chars)
    expiration_date: ?string = null         // Optional - expiration date (ISO 8601 format)
);
```

**Examples:**

```php
use Skald\Types\UpdateMemoData;

// Update by memo UUID (default)
$skald->updateMemo('memo-uuid-here', new UpdateMemoData(
    title: 'Updated Title'
));

// Update by client reference ID
$skald->updateMemo('external-id-123', new UpdateMemoData(
    title: 'Updated via Reference ID'
), 'reference_id');

// Update with project ID (for Token Authentication)
$skald->updateMemo('memo-uuid-here', new UpdateMemoData(
    content: 'New content'
), 'memo_uuid', 'project-uuid-123');

// Update content (triggers automatic reprocessing)
$skald->updateMemo('memo-uuid-here', new UpdateMemoData(
    content: 'New content - this will regenerate summary, tags, and chunks'
));

// Update multiple fields
$skald->updateMemo('memo-uuid-here', new UpdateMemoData(
    title: 'Updated Title',
    metadata: ['updated_at' => time(), 'editor' => 'Jane'],
    source: 'notion',
    expiration_date: '2025-12-31T23:59:59Z'
));

// Update metadata without triggering reprocessing
$skald->updateMemo('memo-uuid-here', new UpdateMemoData(
    metadata: ['last_viewed' => time(), 'view_count' => 42]
));
```

### Deleting Memos

```php
$skald->deleteMemo(
    string $memoId,
    string $idType = 'memo_uuid',
    ?string $projectId = null
): void;
```

Delete a memo and all its associated data (content, summary, tags, chunks).

**Parameters:**

- `$memoId` (string): The memo UUID or client reference ID
- `$idType` (string, optional): Type of identifier - `'memo_uuid'` (default) or `'reference_id'`
- `$projectId` (string|null, optional): Project UUID (required when using Token Authentication)

**Examples:**

```php
// Delete by memo UUID (default)
$skald->deleteMemo('memo-uuid-here');

// Delete by client reference ID
$skald->deleteMemo('external-id-123', 'reference_id');

// Delete with project ID (for Token Authentication)
$skald->deleteMemo('memo-uuid-here', 'memo_uuid', 'project-uuid-123');

// Delete by reference ID with project ID
$skald->deleteMemo('external-id-456', 'reference_id', 'project-uuid-789');
```

### Searching Memos

```php
$response = $skald->search(SearchRequest $searchParams): SearchResponse;
```

**Search Methods:**

- `SearchMethod::CHUNK_VECTOR_SEARCH` - Semantic search on memo chunks (returns distance scores 0-2)
- `SearchMethod::TITLE_CONTAINS` - Case-insensitive substring match on titles
- `SearchMethod::TITLE_STARTSWITH` - Case-insensitive prefix match on titles

**Parameters:**

```php
new SearchRequest(
    query: string,                  // Required - search query
    searchMethod: SearchMethod,     // Required - search method
    limit: ?int = null,            // Optional - results limit (1-50, default 10)
    filters: ?array = null         // Optional - array of Filter objects
);
```

**Example:**

```php
use Skald\Types\Filter;
use Skald\Types\FilterOperator;

// Basic search
$results = $skald->search(new SearchRequest(
    query: 'product requirements',
    searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
    limit: 5
));

foreach ($results->results as $result) {
    echo "UUID: {$result->uuid}\n";
    echo "Title: {$result->title}\n";
    echo "Summary: {$result->summary}\n";
    echo "Snippet: {$result->content_snippet}\n";
    echo "Distance: {$result->distance}\n\n"; // Lower = more relevant
}

// Search with filters
$results = $skald->search(new SearchRequest(
    query: 'product requirements',
    searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
    limit: 5,
    filters: [
        Filter::nativeField('tags', FilterOperator::IN, ['product', 'requirements']),
        Filter::nativeField('source', FilterOperator::EQ, 'confluence')
    ]
));
```

### AI Chat (Non-Streaming)

```php
$response = $skald->chat(ChatRequest $chatParams): ChatResponse;
```

**Parameters:**

```php
new ChatRequest(
    query: string,              // Required - question to ask
);
```

**Example:**

```php
$response = $skald->chat(new ChatRequest(
    query: 'What are the key features of our mobile app?'
));

echo $response->response;
// Output: "The mobile app has several key features: 1. Offline mode [[1]]
//          2. Push notifications [[2]] 3. Biometric authentication [[1]]"

// Citations [[1]], [[2]], etc. reference source memos
```

### AI Chat (Streaming)

```php
$stream = $skald->streamedChat(ChatRequest $chatParams): Generator<ChatStreamEvent>;
```

**Example:**

```php
$stream = $skald->streamedChat(new ChatRequest(
    query: 'Summarize our product roadmap'
));

foreach ($stream as $event) {
    if ($event->isToken()) {
        echo $event->content; // Print each token as it arrives
    } elseif ($event->isDone()) {
        echo "\nDone!\n";
        break;
    }
}
```

### Document Generation (Non-Streaming)

```php
$response = $skald->generateDoc(GenerateDocRequest $generateParams): GenerateDocResponse;
```

**Parameters:**

```php
new GenerateDocRequest(
    prompt: string,             // Required - document generation prompt
    rules: ?string = null,      // Optional - style/format rules
);
```

**Example:**

```php
$response = $skald->generateDoc(new GenerateDocRequest(
    prompt: 'Create a product requirements document for the mobile app',
    rules: 'Use formal business language. Include sections: Overview, Requirements, Timeline'
));

echo $response->response;
// Outputs a full document with inline citations
```

### Document Generation (Streaming)

```php
$stream = $skald->streamedGenerateDoc(GenerateDocRequest $generateParams): Generator<GenerateDocStreamEvent>;
```

**Example:**

```php
$stream = $skald->streamedGenerateDoc(new GenerateDocRequest(
    prompt: 'Write a technical specification for our API',
    rules: 'Include Architecture, Endpoints, and Security sections'
));

foreach ($stream as $event) {
    if ($event->isToken()) {
        echo $event->content;
    } elseif ($event->isDone()) {
        echo "\n[Generation complete]\n";
        break;
    }
}
```

## Error Handling

All API errors throw `Skald\Exceptions\SkaldException`:

```php
use Skald\Exceptions\SkaldException;

try {
    $result = $skald->createMemo(new MemoData(
        title: 'Test',
        content: 'Content'
    ));
} catch (SkaldException $e) {
    // Error format: "Skald API error (STATUS_CODE): ERROR_MESSAGE"
    echo "Error: " . $e->getMessage();
    echo "HTTP Status: " . $e->getCode();
}
```

## Type Reference

### Enums

#### SearchMethod

```php
enum SearchMethod: string
{
    case CHUNK_VECTOR_SEARCH = 'chunk_vector_search';
    case TITLE_CONTAINS = 'title_contains';
    case TITLE_STARTSWITH = 'title_startswith';
}
```

### Request Types

#### MemoData
- `title: string` - Memo title (max 255 characters)
- `content: string` - Memo content
- `metadata: ?array` - Custom metadata
- `reference_id: ?string` - External reference ID
- `tags: ?array` - Array of tag strings
- `source: ?string` - Source system identifier

#### MemoFileData
- `title: ?string` - Memo title (max 255 characters, extracted from file if not provided)
- `metadata: ?array` - Custom metadata
- `reference_id: ?string` - External reference ID
- `tags: ?array` - Array of tag strings
- `source: ?string` - Source system identifier

#### UpdateMemoData
- `title: ?string` - Memo title (max 255 characters)
- `content: ?string` - Memo content (triggers reprocessing when updated)
- `metadata: ?array` - Custom metadata
- `client_reference_id: ?string` - External reference ID (max 255 characters)
- `source: ?string` - Source system identifier (max 255 characters)
- `expiration_date: ?string` - Expiration date in ISO 8601 format

#### SearchRequest
- `query: string` - Search query
- `searchMethod: SearchMethod` - Search method to use
- `limit: ?int` - Results limit (1-50, default 10)
- `filters: ?array` - Array of Filter objects to narrow results

#### ChatRequest
- `query: string` - Question to ask

#### GenerateDocRequest
- `prompt: string` - Document generation prompt
- `rules: ?string` - Style/format guidelines

### Response Types

#### CreateMemoResponse
- `ok: bool` - Success status

#### MemoStatusResponse
- `memo_uuid: string` - The UUID of the memo
- `status: string` - Processing status ('processing', 'processed', or 'error')
- `error_reason: ?string` - Reason for error if status is 'error'
- `isProcessing(): bool` - Check if memo is still processing
- `isProcessed(): bool` - Check if memo has been processed
- `isError(): bool` - Check if memo processing failed

#### SearchResponse
- `results: SearchResult[]` - Array of search results

#### SearchResult
- `uuid: string` - Memo unique identifier
- `title: string` - Memo title
- `summary: string` - Auto-generated summary
- `content_snippet: string` - Content snippet
- `distance: ?float` - Relevance score (0-2 for vector search, null for title searches)

#### ChatResponse
- `ok: bool` - Success status
- `response: string` - AI response with inline citations
- `intermediate_steps: array` - Debug information

#### GenerateDocResponse
- `ok: bool` - Success status
- `response: string` - Generated document with citations
- `intermediate_steps: array` - Debug information

### Stream Event Types

#### ChatStreamEvent & GenerateDocStreamEvent
- `type: string` - Event type ('token' or 'done')
- `content: ?string` - Token content (only for 'token' events)
- `isToken(): bool` - Check if event is a token
- `isDone(): bool` - Check if event signals completion

## Examples

See the `examples/` directory for complete working examples:

- `create_memo.php` - Creating memos with various options
- `upload_file.php` - Uploading documents (PDF, DOC, DOCX, PPTX)
- `check_memo_status.php` - Checking memo processing status with polling
- `update_memo.php` - Updating existing memos (including by reference ID)
- `delete_memo.php` - Deleting memos (by UUID or reference ID)
- `search.php` - All search methods with examples
- `chat.php` - Non-streaming chat
- `chat_streaming.php` - Streaming chat with real-time output
- `generate_doc.php` - Document generation
- `generate_doc_streaming.php` - Streaming document generation

## Testing

Run the test suite:

```bash
# Install dependencies
composer install

# Run unit tests
composer test

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run static analysis
composer phpstan

# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

### Integration Tests

Integration tests require a valid Skald API key:

```bash
export SKALD_API_KEY=sk_proj_your_api_key
composer test
```

## Development

### Code Quality Tools

This library uses:
- **PHPUnit** for testing
- **PHPStan** (level 8) for static analysis
- **PHP_CodeSniffer** for PSR-12 compliance

Run all checks:

```bash
composer test      # Run tests
composer phpstan   # Static analysis
composer cs-check  # Code style check
```

## License

MIT License - Copyright (c) 2025 Skald Labs, Inc.

See [LICENSE](LICENSE) file for details.


## Contributing

If you've spotted a bug or want a new feature, feel free to submit a PR. 