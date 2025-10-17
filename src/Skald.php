<?php

declare(strict_types=1);

namespace Skald;

use Generator;
use Skald\Exceptions\SkaldException;
use Skald\Types\ChatRequest;
use Skald\Types\ChatResponse;
use Skald\Types\ChatStreamEvent;
use Skald\Types\CreateMemoResponse;
use Skald\Types\GenerateDocRequest;
use Skald\Types\GenerateDocResponse;
use Skald\Types\GenerateDocStreamEvent;
use Skald\Types\MemoData;
use Skald\Types\SearchRequest;
use Skald\Types\SearchResponse;
use Skald\Types\UpdateMemoData;

/**
 * Skald API Client for PHP.
 *
 * A knowledge base management system that automatically processes memos
 * (summarizes, chunks, and indexes them) and provides semantic search,
 * AI chat, and document generation capabilities.
 *
 * @example
 * ```php
 * $skald = new Skald('sk_proj_your_api_key');
 *
 * // Create a memo
 * $result = $skald->createMemo(new MemoData(
 *     title: 'Meeting Notes',
 *     content: 'Discussion about Q1 roadmap...'
 * ));
 *
 * // Search memos
 * $results = $skald->search(new SearchRequest(
 *     query: 'quarterly goals',
 *     searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH
 * ));
 *
 * // Chat with your knowledge base
 * $response = $skald->chat(new ChatRequest(
 *     query: 'What were the main points discussed?'
 * ));
 * ```
 */
final class Skald
{
    private const DEFAULT_BASE_URL = 'https://api.useskald.com';

    private string $apiKey;
    private string $baseUrl;

    /**
     * Create a new Skald API client.
     *
     * @param string $apiKey API authentication key
     * @param string|null $baseUrl Optional custom base URL (defaults to https://api.useskald.com)
     */
    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
    }

    /**
     * Create a new memo that will be automatically processed by the Skald API.
     *
     * @param MemoData $memoData The memo data to create
     * @return CreateMemoResponse
     * @throws SkaldException
     */
    public function createMemo(MemoData $memoData): CreateMemoResponse
    {
        $response = $this->post('/api/v1/memo', $memoData->toArray());
        return CreateMemoResponse::fromArray($response);
    }

    /**
     * Update an existing memo.
     *
     * All fields are optional. When content is updated, the memo is automatically
     * reprocessed (summary, tags, and chunks are regenerated).
     *
     * @param string $memoId The UUID or client reference ID of the memo to update
     * @param UpdateMemoData $updateData The fields to update
     * @param string $idType Type of identifier: 'memo_uuid' or 'reference_id' (default: 'memo_uuid')
     * @param string|null $projectId Project UUID (required when using Token Authentication)
     * @return CreateMemoResponse
     * @throws SkaldException
     */
    public function updateMemo(
        string $memoId,
        UpdateMemoData $updateData,
        string $idType = 'memo_uuid',
    ): CreateMemoResponse {
        $queryParams = [];

        if ($idType !== 'memo_uuid') {
            $queryParams['id_type'] = $idType;
        }


        $endpoint = "/api/v1/memo/{$memoId}";
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $response = $this->patch($endpoint, $updateData->toArray());
        return CreateMemoResponse::fromArray($response);
    }

    /**
     * Delete a memo and all its associated data.
     *
     * @param string $memoId The UUID or client reference ID of the memo to delete
     * @param string $idType Type of identifier: 'memo_uuid' or 'reference_id' (default: 'memo_uuid')
     * @return void
     * @throws SkaldException
     */
    public function deleteMemo(
        string $memoId,
        string $idType = 'memo_uuid',
    ): void {
        $queryParams = [];

        if ($idType !== 'memo_uuid') {
            $queryParams['id_type'] = $idType;
        }

        $endpoint = "/api/v1/memo/{$memoId}";
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $this->delete($endpoint);
    }

    /**
     * Search through memos using various search methods.
     *
     * @param SearchRequest $searchParams Search parameters
     * @return SearchResponse
     * @throws SkaldException
     */
    public function search(SearchRequest $searchParams): SearchResponse
    {
        $response = $this->post('/api/v1/search', $searchParams->toArray());
        return SearchResponse::fromArray($response);
    }

    /**
     * Ask questions about the knowledge base using an AI agent (non-streaming).
     *
     * @param ChatRequest $chatParams Chat parameters
     * @return ChatResponse
     * @throws SkaldException
     */
    public function chat(ChatRequest $chatParams): ChatResponse
    {
        $response = $this->post('/api/v1/chat', $chatParams->toArray(false));
        return ChatResponse::fromArray($response);
    }

    /**
     * Ask questions with streaming responses.
     *
     * Returns a generator that yields ChatStreamEvent objects.
     * Events have a type ('token' or 'done') and optional content.
     *
     * @param ChatRequest $chatParams Chat parameters
     * @return Generator<ChatStreamEvent>
     * @throws SkaldException
     *
     * @example
     * ```php
     * $stream = $skald->streamedChat(new ChatRequest('What are our goals?'));
     * foreach ($stream as $event) {
     *     if ($event->isToken()) {
     *         echo $event->content;
     *     } elseif ($event->isDone()) {
     *         echo "\nDone!\n";
     *     }
     * }
     * ```
     */
    public function streamedChat(ChatRequest $chatParams): Generator
    {
        yield from $this->streamPost('/api/v1/chat', $chatParams->toArray(true), ChatStreamEvent::class);
    }

    /**
     * Generate documents based on prompts and retrieved context (non-streaming).
     *
     * @param GenerateDocRequest $generateParams Generation parameters
     * @return GenerateDocResponse
     * @throws SkaldException
     */
    public function generateDoc(GenerateDocRequest $generateParams): GenerateDocResponse
    {
        $response = $this->post('/api/v1/generate', $generateParams->toArray(false));
        return GenerateDocResponse::fromArray($response);
    }

    /**
     * Generate documents with streaming responses.
     *
     * Returns a generator that yields GenerateDocStreamEvent objects.
     * Events have a type ('token' or 'done') and optional content.
     *
     * @param GenerateDocRequest $generateParams Generation parameters
     * @return Generator<GenerateDocStreamEvent>
     * @throws SkaldException
     *
     * @example
     * ```php
     * $stream = $skald->streamedGenerateDoc(new GenerateDocRequest(
     *     prompt: 'Write a technical specification',
     *     rules: 'Include Architecture section'
     * ));
     * foreach ($stream as $event) {
     *     if ($event->isToken()) {
     *         echo $event->content;
     *     }
     * }
     * ```
     */
    public function streamedGenerateDoc(GenerateDocRequest $generateParams): Generator
    {
        yield from $this->streamPost('/api/v1/generate', $generateParams->toArray(true), GenerateDocStreamEvent::class);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed>
     * @throws SkaldException
     */
    private function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Make a PATCH request to the API.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed>
     * @throws SkaldException
     */
    private function patch(string $endpoint, array $data): array
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @param string $endpoint API endpoint path
     * @return void
     * @throws SkaldException
     */
    private function delete(string $endpoint): void
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        if ($ch === false) {
            throw new SkaldException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new SkaldException('cURL request failed: ' . $error);
        }

        // DELETE returns 204 No Content on success
        if ($httpCode >= 400) {
            throw SkaldException::fromApiError($httpCode, (string)$response);
        }
    }

    /**
     * Make an HTTP request to the API.
     *
     * @param string $method HTTP method (POST, PATCH, etc.)
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed>
     * @throws SkaldException
     */
    private function request(string $method, string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;
        $jsonData = json_encode($data);

        if ($jsonData === false) {
            throw new SkaldException('Failed to encode request data as JSON');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new SkaldException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new SkaldException('cURL request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            throw SkaldException::fromApiError($httpCode, (string)$response);
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            throw new SkaldException('Failed to decode API response as JSON');
        }

        return $decoded;
    }

    /**
     * Make a streaming POST request to the API.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request body data
     * @param class-string $eventClass Event class to instantiate
     * @return Generator
     * @throws SkaldException
     */
    private function streamPost(string $endpoint, array $data, string $eventClass): Generator
    {
        $url = $this->baseUrl . $endpoint;
        $jsonData = json_encode($data);

        if ($jsonData === false) {
            throw new SkaldException('Failed to encode request data as JSON');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new SkaldException('Failed to initialize cURL');
        }

        $buffer = '';
        $httpCode = 0;

        // Write function that processes streamed data
        $writeFunction = function ($curl, $data) use (&$buffer, &$httpCode, $eventClass) {
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($httpCode >= 400) {
                // Collect error response
                $buffer .= $data;
                return strlen($data);
            }

            $buffer .= $data;
            $lines = explode("\n", $buffer);

            // Keep the last incomplete line in the buffer
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                // Skip empty lines and ping lines
                if (trim($line) === '' || str_starts_with($line, ':')) {
                    continue;
                }

                // Parse SSE format: "data: {...}"
                if (str_starts_with($line, 'data: ')) {
                    $jsonStr = substr($line, 6);
                    try {
                        $eventData = json_decode($jsonStr, true);
                        if (is_array($eventData)) {
                            // Store in static variable to pass to generator
                            self::$lastEvent = $eventClass::fromArray($eventData);
                        }
                    } catch (\Throwable $e) {
                        // Silently skip invalid JSON as per spec
                        continue;
                    }
                }
            }

            return strlen($data);
        };

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_WRITEFUNCTION => $writeFunction,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        // We need a different approach - use a callback that yields
        // Since we can't yield from inside a callback, we'll use stream context instead
        curl_close($ch);

        // Use stream context for better streaming support
        yield from $this->streamWithContext($url, $jsonData, $eventClass);
    }

    /**
     * Last event captured during streaming (used for communication between callback and generator).
     *
     * @var mixed
     */
    private static $lastEvent = null;

    /**
     * Stream using PHP stream context.
     *
     * @param string $url
     * @param string $jsonData
     * @param class-string $eventClass
     * @return Generator
     * @throws SkaldException
     */
    private function streamWithContext(string $url, string $jsonData, string $eventClass): Generator
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                'content' => $jsonData,
                'timeout' => 300, // 5 minute timeout for long streams
            ],
        ];

        $context = stream_context_create($opts);
        $stream = @fopen($url, 'r', false, $context);

        if ($stream === false) {
            throw new SkaldException('Failed to open stream to API');
        }

        try {
            // Check HTTP status from headers
            $metadata = stream_get_meta_data($stream);
            $httpCode = 200;

            if (isset($metadata['wrapper_data']) && is_array($metadata['wrapper_data'])) {
                foreach ($metadata['wrapper_data'] as $header) {
                    if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                        $httpCode = (int)$matches[1];
                        break;
                    }
                }
            }

            if ($httpCode >= 400) {
                $errorBody = stream_get_contents($stream);
                throw SkaldException::fromApiError($httpCode, $errorBody ?: 'Unknown error');
            }

            // Set stream to non-blocking mode for real-time streaming
            stream_set_blocking($stream, false);

            $buffer = '';
            $lastReadTime = time();

            while (!feof($stream)) {
                // Read small chunks to enable real-time streaming
                $chunk = fread($stream, 8192);

                if ($chunk === false || $chunk === '') {
                    // No data available, check timeout
                    if (time() - $lastReadTime > 30) {
                        // 30 second timeout with no data
                        break;
                    }
                    // Sleep briefly to avoid busy waiting
                    usleep(10000); // 10ms
                    continue;
                }

                $lastReadTime = time();
                $buffer .= $chunk;
                $lines = explode("\n", $buffer);

                // Keep the last incomplete line in buffer
                if (!str_ends_with($buffer, "\n")) {
                    $buffer = array_pop($lines);
                } else {
                    $buffer = '';
                }

                foreach ($lines as $line) {
                    $line = trim($line);

                    // Skip empty lines and ping lines
                    if ($line === '' || str_starts_with($line, ':')) {
                        continue;
                    }

                    // Parse SSE format: "data: {...}"
                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = substr($line, 6);
                        try {
                            $eventData = json_decode($jsonStr, true);
                            if (is_array($eventData)) {
                                $event = $eventClass::fromArray($eventData);
                                yield $event;

                                // Stop after 'done' event
                                if (isset($eventData['type']) && $eventData['type'] === 'done') {
                                    return;
                                }
                            }
                        } catch (\Throwable $e) {
                            // Silently skip invalid JSON as per spec
                            continue;
                        }
                    }
                }
            }
        } finally {
            fclose($stream);
        }
    }
}
