<?php

declare(strict_types=1);

namespace Skald\Tests;

use PHPUnit\Framework\TestCase;
use Skald\Exceptions\SkaldException;
use Skald\Skald;
use Skald\Types\ChatRequest;
use Skald\Types\Filter;
use Skald\Types\FilterOperator;
use Skald\Types\GenerateDocRequest;
use Skald\Types\MemoData;
use Skald\Types\MemoFileData;
use Skald\Types\SearchMethod;
use Skald\Types\SearchRequest;
use Skald\Types\UpdateMemoData;

/**
 * Unit tests for Skald PHP SDK with mocked API responses.
 *
 * These tests verify the library's behavior without making real API calls.
 * Tests use a mock HTTP server to simulate API responses.
 */
class SkaldTest extends TestCase
{
    private MockHttpServer $mockServer;
    private Skald $client;

    protected function setUp(): void
    {
        $this->mockServer = new MockHttpServer();
        $this->mockServer->start();
        $this->client = new Skald('test_api_key', $this->mockServer->getBaseUrl());
    }

    protected function tearDown(): void
    {
        $this->mockServer->stop();
    }

    public function testConstructorWithDefaultBaseUrl(): void
    {
        $client = new Skald('test_key');
        $this->assertInstanceOf(Skald::class, $client);
    }

    public function testConstructorWithCustomBaseUrl(): void
    {
        $client = new Skald('test_key', 'https://custom.api.com/');
        $this->assertInstanceOf(Skald::class, $client);
    }

    public function testCreateMemoSuccess(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $memoData = new MemoData(
            title: 'Test Memo',
            content: 'This is a test memo created by the PHP SDK test suite.',
            metadata: ['test' => true, 'timestamp' => 1234567890],
            tags: ['test', 'php-sdk'],
            source: 'phpunit'
        );

        $response = $this->client->createMemo($memoData);

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $this->assertEquals('/api/v1/memo', $request['path']);
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('Bearer test_api_key', $request['headers']['Authorization']);
    }

    public function testCreateMemoWithMinimalData(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $memoData = new MemoData(
            title: 'Minimal Test Memo',
            content: 'Minimal content for testing.'
        );

        $response = $this->client->createMemo($memoData);

        $this->assertTrue($response->ok);
    }

    public function testSearchWithChunkVectorSearch(): void
    {
        $mockResults = [
            'results' => [
                [
                    'uuid' => 'test-uuid-1',
                    'title' => 'Test Result 1',
                    'summary' => 'Summary 1',
                    'content_snippet' => 'Content snippet 1',
                    'distance' => 0.25,
                ],
                [
                    'uuid' => 'test-uuid-2',
                    'title' => 'Test Result 2',
                    'summary' => 'Summary 2',
                    'content_snippet' => 'Content snippet 2',
                    'distance' => 0.5,
                ],
            ],
        ];

        $this->mockServer->queueResponse(200, $mockResults);

        $searchRequest = new SearchRequest(
            query: 'test',
            searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
            limit: 5
        );

        $response = $this->client->search($searchRequest);

        $this->assertIsArray($response->results);
        $this->assertCount(2, $response->results);

        $this->assertEquals('test-uuid-1', $response->results[0]->uuid);
        $this->assertEquals('Test Result 1', $response->results[0]->title);
        $this->assertIsFloat($response->results[0]->distance);
        $this->assertEquals(0.25, $response->results[0]->distance);
    }

    public function testSearchWithTitleContains(): void
    {
        $mockResults = [
            'results' => [
                [
                    'uuid' => 'test-uuid-3',
                    'title' => 'Test Title',
                    'summary' => 'Summary',
                    'content_snippet' => 'Content',
                    'distance' => null,
                ],
            ],
        ];

        $this->mockServer->queueResponse(200, $mockResults);

        $searchRequest = new SearchRequest(
            query: 'Test',
            searchMethod: SearchMethod::TITLE_CONTAINS,
            limit: 10
        );

        $response = $this->client->search($searchRequest);

        $this->assertIsArray($response->results);
        $this->assertCount(1, $response->results);
        $this->assertNull($response->results[0]->distance);
    }

    public function testSearchWithTitleStartsWith(): void
    {
        $mockResults = [
            'results' => [],
        ];

        $this->mockServer->queueResponse(200, $mockResults);

        $searchRequest = new SearchRequest(
            query: 'Test',
            searchMethod: SearchMethod::TITLE_STARTSWITH
        );

        $response = $this->client->search($searchRequest);

        $this->assertIsArray($response->results);
        $this->assertEmpty($response->results);
    }

    public function testChatSuccess(): void
    {
        $mockResponse = [
            'ok' => true,
            'response' => 'This is a test response from the chat API.',
            'intermediate_steps' => [
                ['action' => 'search', 'query' => 'testing'],
            ],
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $chatRequest = new ChatRequest(
            query: 'What information do you have about testing?'
        );

        $response = $this->client->chat($chatRequest);

        $this->assertTrue($response->ok);
        $this->assertIsString($response->response);
        $this->assertEquals('This is a test response from the chat API.', $response->response);
        $this->assertIsArray($response->intermediate_steps);
        $this->assertCount(1, $response->intermediate_steps);
    }

    public function testStreamedChatSuccess(): void
    {
        $mockStreamData = [
            "data: {\"type\":\"token\",\"content\":\"Hello\"}\n",
            "data: {\"type\":\"token\",\"content\":\" world\"}\n",
            "data: {\"type\":\"done\"}\n",
        ];

        $this->mockServer->queueStreamResponse(200, $mockStreamData);

        $chatRequest = new ChatRequest(
            query: 'Briefly describe what you know.'
        );

        $stream = $this->client->streamedChat($chatRequest);

        $tokenCount = 0;
        $doneCount = 0;
        $content = '';

        foreach ($stream as $event) {
            $this->assertContains($event->type, ['token', 'done']);

            if ($event->isToken()) {
                $tokenCount++;
                if ($event->content !== null) {
                    $content .= $event->content;
                }
            } elseif ($event->isDone()) {
                $doneCount++;
            }
        }

        $this->assertEquals(2, $tokenCount, 'Should receive two token events');
        $this->assertEquals(1, $doneCount, 'Should receive exactly one done event');
        $this->assertEquals('Hello world', $content);
    }

    public function testGenerateDocSuccess(): void
    {
        $mockResponse = [
            'ok' => true,
            'response' => 'This is a generated document based on the prompt.',
            'intermediate_steps' => [],
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $generateRequest = new GenerateDocRequest(
            prompt: 'Write a brief summary of available test-related information.',
            rules: 'Keep it under 100 words. Use formal language.'
        );

        $response = $this->client->generateDoc($generateRequest);

        $this->assertTrue($response->ok);
        $this->assertIsString($response->response);
        $this->assertEquals('This is a generated document based on the prompt.', $response->response);
        $this->assertIsArray($response->intermediate_steps);
    }

    public function testStreamedGenerateDocSuccess(): void
    {
        $mockStreamData = [
            "data: {\"type\":\"token\",\"content\":\"Generated\"}\n",
            "data: {\"type\":\"token\",\"content\":\" content\"}\n",
            "data: {\"type\":\"done\"}\n",
        ];

        $this->mockServer->queueStreamResponse(200, $mockStreamData);

        $generateRequest = new GenerateDocRequest(
            prompt: 'Write a one-sentence summary.'
        );

        $stream = $this->client->streamedGenerateDoc($generateRequest);

        $tokenCount = 0;
        $doneCount = 0;
        $content = '';

        foreach ($stream as $event) {
            $this->assertContains($event->type, ['token', 'done']);

            if ($event->isToken()) {
                $tokenCount++;
                if ($event->content !== null) {
                    $content .= $event->content;
                }
            } elseif ($event->isDone()) {
                $doneCount++;
            }
        }

        $this->assertEquals(2, $tokenCount, 'Should receive two token events');
        $this->assertEquals(1, $doneCount, 'Should receive exactly one done event');
        $this->assertEquals('Generated content', $content);
    }

    public function testInvalidApiKeyThrowsException(): void
    {
        $mockErrorResponse = [
            'error' => 'Invalid API key',
        ];

        $this->mockServer->queueResponse(401, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(401\):/');

        $this->client->createMemo(new MemoData(
            title: 'Test',
            content: 'Test'
        ));
    }

    public function testMemoDataToArray(): void
    {
        $memoData = new MemoData(
            title: 'Test Title',
            content: 'Test Content',
            metadata: ['key' => 'value'],
            reference_id: 'ref123',
            tags: ['tag1', 'tag2'],
            source: 'test'
        );

        $array = $memoData->toArray();

        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals('Test Content', $array['content']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertEquals('ref123', $array['reference_id']);
        $this->assertEquals(['tag1', 'tag2'], $array['tags']);
        $this->assertEquals('test', $array['source']);
    }

    public function testMemoDataToArrayWithDefaults(): void
    {
        $memoData = new MemoData(
            title: 'Test',
            content: 'Content'
        );

        $array = $memoData->toArray();

        $this->assertEquals('Test', $array['title']);
        $this->assertEquals('Content', $array['content']);
        $this->assertEquals([], $array['metadata']);
        $this->assertArrayNotHasKey('reference_id', $array);
        $this->assertArrayNotHasKey('tags', $array);
        $this->assertArrayNotHasKey('source', $array);
    }

    public function testUpdateMemoSuccess(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $updateData = new UpdateMemoData(
            title: 'Updated Title',
            content: 'Updated content'
        );

        $response = $this->client->updateMemo('test-memo-uuid', $updateData);

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $this->assertEquals('/api/v1/memo/test-memo-uuid', $request['path']);
        $this->assertEquals('PATCH', $request['method']);
        $this->assertStringContainsString('Bearer test_api_key', $request['headers']['Authorization']);

        $body = json_decode($request['body'], true);
        $this->assertEquals('Updated Title', $body['title']);
        $this->assertEquals('Updated content', $body['content']);
    }

    public function testUpdateMemoWithAllFields(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $updateData = new UpdateMemoData(
            title: 'New Title',
            content: 'New content',
            metadata: ['updated' => true],
            client_reference_id: 'new-ref-123',
            source: 'updated-source',
            expiration_date: '2025-12-31T23:59:59Z'
        );

        $response = $this->client->updateMemo('memo-uuid', $updateData);

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $body = json_decode($request['body'], true);
        $this->assertEquals('New Title', $body['title']);
        $this->assertEquals('New content', $body['content']);
        $this->assertEquals(['updated' => true], $body['metadata']);
        $this->assertEquals('new-ref-123', $body['client_reference_id']);
        $this->assertEquals('updated-source', $body['source']);
        $this->assertEquals('2025-12-31T23:59:59Z', $body['expiration_date']);
    }

    public function testUpdateMemoWithPartialFields(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $updateData = new UpdateMemoData(
            title: 'Only Title Updated'
        );

        $response = $this->client->updateMemo('memo-uuid', $updateData);

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $body = json_decode($request['body'], true);
        $this->assertEquals('Only Title Updated', $body['title']);
        $this->assertArrayNotHasKey('content', $body);
        $this->assertArrayNotHasKey('metadata', $body);
        $this->assertArrayNotHasKey('client_reference_id', $body);
        $this->assertArrayNotHasKey('source', $body);
        $this->assertArrayNotHasKey('expiration_date', $body);
    }

    public function testUpdateMemoNotFound(): void
    {
        $mockErrorResponse = [
            'error' => 'Memo not found',
        ];

        $this->mockServer->queueResponse(404, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(404\):/');

        $this->client->updateMemo('non-existent-uuid', new UpdateMemoData(
            title: 'Test'
        ));
    }

    public function testUpdateMemoDataToArray(): void
    {
        $updateData = new UpdateMemoData(
            title: 'Test Title',
            content: 'Test Content',
            metadata: ['key' => 'value'],
            client_reference_id: 'ref123',
            source: 'test',
            expiration_date: '2025-12-31T23:59:59Z'
        );

        $array = $updateData->toArray();

        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals('Test Content', $array['content']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertEquals('ref123', $array['client_reference_id']);
        $this->assertEquals('test', $array['source']);
        $this->assertEquals('2025-12-31T23:59:59Z', $array['expiration_date']);
    }

    public function testUpdateMemoDataToArrayWithDefaults(): void
    {
        $updateData = new UpdateMemoData();

        $array = $updateData->toArray();

        $this->assertEmpty($array);
        $this->assertArrayNotHasKey('title', $array);
        $this->assertArrayNotHasKey('content', $array);
        $this->assertArrayNotHasKey('metadata', $array);
        $this->assertArrayNotHasKey('client_reference_id', $array);
        $this->assertArrayNotHasKey('source', $array);
        $this->assertArrayNotHasKey('expiration_date', $array);
    }

    public function testUpdateMemoWithReferenceId(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $updateData = new UpdateMemoData(
            title: 'Updated via Reference ID'
        );

        $response = $this->client->updateMemo('external-ref-123', $updateData, 'reference_id');

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $this->assertStringContainsString('id_type=reference_id', $request['path']);
        $this->assertStringContainsString('/api/v1/memo/external-ref-123', $request['path']);
        $this->assertEquals('PATCH', $request['method']);
    }


    public function testUpdateMemoWithReferenceIdAndProjectId(): void
    {
        $this->mockServer->queueResponse(200, ['ok' => true]);

        $updateData = new UpdateMemoData(
            content: 'New content'
        );

        $response = $this->client->updateMemo(
            'external-ref-456',
            $updateData,
            'reference_id',
        );

        $this->assertTrue($response->ok);

        $request = $this->mockServer->getLastRequest();
        $this->assertStringContainsString('id_type=reference_id', $request['path']);
        $this->assertStringContainsString('/api/v1/memo/external-ref-456', $request['path']);
    }

    public function testDeleteMemoSuccess(): void
    {
        $this->mockServer->queueResponse(204, null);

        $this->client->deleteMemo('test-memo-uuid');

        $request = $this->mockServer->getLastRequest();
        $this->assertEquals('/api/v1/memo/test-memo-uuid', $request['path']);
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('Bearer test_api_key', $request['headers']['Authorization']);
    }

    public function testDeleteMemoWithReferenceId(): void
    {
        $this->mockServer->queueResponse(204, null);

        $this->client->deleteMemo('external-ref-123', 'reference_id');

        $request = $this->mockServer->getLastRequest();
        $this->assertStringContainsString('id_type=reference_id', $request['path']);
        $this->assertStringContainsString('/api/v1/memo/external-ref-123', $request['path']);
        $this->assertEquals('DELETE', $request['method']);
    }


    public function testDeleteMemoWithReferenceIdAndProjectId(): void
    {
        $this->mockServer->queueResponse(204, null);

        $this->client->deleteMemo('external-ref-789', 'reference_id', 'project-uuid-456');

        $request = $this->mockServer->getLastRequest();
        $this->assertStringContainsString('id_type=reference_id', $request['path']);
        $this->assertStringContainsString('/api/v1/memo/external-ref-789', $request['path']);
    }

    public function testDeleteMemoNotFound(): void
    {
        $mockErrorResponse = [
            'error' => 'Memo not found',
        ];

        $this->mockServer->queueResponse(404, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(404\):/');

        $this->client->deleteMemo('non-existent-uuid');
    }

    public function testDeleteMemoAccessDenied(): void
    {
        $mockErrorResponse = [
            'error' => 'Access denied',
        ];

        $this->mockServer->queueResponse(403, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(403\):/');

        $this->client->deleteMemo('forbidden-uuid');
    }

    public function testUpdateMemoInvalidIdType(): void
    {
        $mockErrorResponse = [
            'error' => "id_type must be either 'memo_uuid' or 'reference_id'",
        ];

        $this->mockServer->queueResponse(400, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(400\):/');

        $this->client->updateMemo('memo-id', new UpdateMemoData(title: 'Test'), 'invalid_type');
    }

    public function testDeleteMemoInvalidIdType(): void
    {
        $mockErrorResponse = [
            'error' => "id_type must be either 'memo_uuid' or 'reference_id'",
        ];

        $this->mockServer->queueResponse(400, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(400\):/');

        $this->client->deleteMemo('memo-id', 'invalid_type');
    }

    public function testSearchMethodEnum(): void
    {
        $this->assertEquals('chunk_vector_search', SearchMethod::CHUNK_VECTOR_SEARCH->value);
        $this->assertEquals('title_contains', SearchMethod::TITLE_CONTAINS->value);
        $this->assertEquals('title_startswith', SearchMethod::TITLE_STARTSWITH->value);
    }

    public function testChatRequestToArray(): void
    {
        $request = new ChatRequest(
            query: 'Test query',
        );

        $array = $request->toArray(false);
        $this->assertEquals('Test query', $array['query']);
        $this->assertFalse($array['stream']);

        $streamArray = $request->toArray(true);
        $this->assertTrue($streamArray['stream']);
    }

    public function testGenerateDocRequestToArray(): void
    {
        $request = new GenerateDocRequest(
            prompt: 'Test prompt',
            rules: 'Test rules',
        );

        $array = $request->toArray(false);
        $this->assertEquals('Test prompt', $array['prompt']);
        $this->assertEquals('Test rules', $array['rules']);
        $this->assertFalse($array['stream']);
    }

    public function testSearchWithFilters(): void
    {
        $mockResults = [
            'results' => [
                [
                    'uuid' => 'filtered-uuid-1',
                    'title' => 'Filtered Result',
                    'summary' => 'Summary',
                    'content_snippet' => 'Snippet',
                    'distance' => 0.3,
                ],
            ],
        ];

        $this->mockServer->queueResponse(200, $mockResults);

        $filters = [
            Filter::nativeField('source', FilterOperator::EQ, 'notion'),
            Filter::customMetadata('department', FilterOperator::CONTAINS, 'engineering'),
        ];

        $searchRequest = new SearchRequest(
            query: 'test query',
            searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
            limit: 10,
            filters: $filters
        );

        $response = $this->client->search($searchRequest);

        $this->assertCount(1, $response->results);
        $this->assertEquals('filtered-uuid-1', $response->results[0]->uuid);

        // Verify filters were sent in the request
        $request = $this->mockServer->getLastRequest();
        $body = json_decode($request['body'], true);
        $this->assertArrayHasKey('filters', $body);
        $this->assertCount(2, $body['filters']);
        $this->assertEquals('source', $body['filters'][0]['field']);
        $this->assertEquals('eq', $body['filters'][0]['operator']);
        $this->assertEquals('notion', $body['filters'][0]['value']);
        $this->assertEquals('native_field', $body['filters'][0]['filter_type']);
    }

    public function testChatWithFilters(): void
    {
        $mockResponse = [
            'ok' => true,
            'response' => 'Filtered chat response',
            'intermediate_steps' => [],
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $filters = [
            Filter::nativeField('tags', FilterOperator::IN, ['important', 'urgent']),
        ];

        $chatRequest = new ChatRequest(
            query: 'What are the urgent items?',
            filters: $filters
        );

        $response = $this->client->chat($chatRequest);

        $this->assertTrue($response->ok);
        $this->assertEquals('Filtered chat response', $response->response);

        // Verify filters were sent in the request
        $request = $this->mockServer->getLastRequest();
        $body = json_decode($request['body'], true);
        $this->assertArrayHasKey('filters', $body);
        $this->assertCount(1, $body['filters']);
        $this->assertEquals('tags', $body['filters'][0]['field']);
        $this->assertEquals('in', $body['filters'][0]['operator']);
    }

    public function testGenerateDocWithFilters(): void
    {
        $mockResponse = [
            'ok' => true,
            'response' => 'Filtered generated document',
            'intermediate_steps' => [],
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $filters = [
            Filter::nativeField('source', FilterOperator::EQ, 'confluence'),
        ];

        $generateRequest = new GenerateDocRequest(
            prompt: 'Create a summary',
            rules: 'Be concise',
            filters: $filters
        );

        $response = $this->client->generateDoc($generateRequest);

        $this->assertTrue($response->ok);
        $this->assertEquals('Filtered generated document', $response->response);

        // Verify filters were sent in the request
        $request = $this->mockServer->getLastRequest();
        $body = json_decode($request['body'], true);
        $this->assertArrayHasKey('filters', $body);
        $this->assertCount(1, $body['filters']);
    }

    public function testCreateMemoFromFileSuccess(): void
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test-memo-' . uniqid() . '.txt';
        file_put_contents($tempFile, 'This is test content for file upload.');

        $this->mockServer->queueResponse(200, ['ok' => true]);

        $memoData = new MemoFileData(
            title: 'Test File Upload',
            metadata: ['test' => true],
            tags: ['test', 'upload'],
            source: 'phpunit'
        );

        try {
            $response = $this->client->createMemoFromFile($tempFile, $memoData);

            $this->assertTrue($response->ok);

            $request = $this->mockServer->getLastRequest();
            $this->assertEquals('/api/v1/memo/upload', $request['path']);
            $this->assertEquals('POST', $request['method']);
            $this->assertStringContainsString('Bearer test_api_key', $request['headers']['Authorization']);
            $this->assertStringContainsString('multipart/form-data', $request['headers']['Content-Type']);
        } finally {
            @unlink($tempFile);
        }
    }

    public function testCreateMemoFromFileWithoutMetadata(): void
    {
        // Create a temporary test file
        $tempFile = sys_get_temp_dir() . '/test-memo-minimal-' . uniqid() . '.txt';
        file_put_contents($tempFile, 'Minimal test content.');

        $this->mockServer->queueResponse(200, ['ok' => true]);

        try {
            $response = $this->client->createMemoFromFile($tempFile);

            $this->assertTrue($response->ok);

            $request = $this->mockServer->getLastRequest();
            $this->assertEquals('/api/v1/memo/upload', $request['path']);
            $this->assertEquals('POST', $request['method']);
        } finally {
            @unlink($tempFile);
        }
    }

    public function testCreateMemoFromFileNotFound(): void
    {
        $this->expectException(SkaldException::class);
        $this->expectExceptionMessage('File not found');

        $this->client->createMemoFromFile('/nonexistent/file.txt');
    }

    public function testCheckMemoStatusProcessing(): void
    {
        $mockResponse = [
            'memo_uuid' => 'test-uuid-123',
            'status' => 'processing',
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $response = $this->client->checkMemoStatus('test-uuid-123');

        $this->assertEquals('test-uuid-123', $response->memo_uuid);
        $this->assertEquals('processing', $response->status);
        $this->assertTrue($response->isProcessing());
        $this->assertFalse($response->isProcessed());
        $this->assertFalse($response->isError());
        $this->assertNull($response->error_reason);

        $request = $this->mockServer->getLastRequest();
        $this->assertEquals('/api/v1/memo/test-uuid-123/status', $request['path']);
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('Bearer test_api_key', $request['headers']['Authorization']);
    }

    public function testCheckMemoStatusProcessed(): void
    {
        $mockResponse = [
            'memo_uuid' => 'test-uuid-456',
            'status' => 'processed',
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $response = $this->client->checkMemoStatus('test-uuid-456');

        $this->assertEquals('test-uuid-456', $response->memo_uuid);
        $this->assertEquals('processed', $response->status);
        $this->assertFalse($response->isProcessing());
        $this->assertTrue($response->isProcessed());
        $this->assertFalse($response->isError());
    }

    public function testCheckMemoStatusError(): void
    {
        $mockResponse = [
            'memo_uuid' => 'test-uuid-789',
            'status' => 'error',
            'error_reason' => 'File format not supported',
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $response = $this->client->checkMemoStatus('test-uuid-789');

        $this->assertEquals('test-uuid-789', $response->memo_uuid);
        $this->assertEquals('error', $response->status);
        $this->assertFalse($response->isProcessing());
        $this->assertFalse($response->isProcessed());
        $this->assertTrue($response->isError());
        $this->assertEquals('File format not supported', $response->error_reason);
    }

    public function testCheckMemoStatusWithReferenceId(): void
    {
        $mockResponse = [
            'memo_uuid' => 'test-uuid-abc',
            'status' => 'processed',
        ];

        $this->mockServer->queueResponse(200, $mockResponse);

        $response = $this->client->checkMemoStatus('external-ref-123', 'reference_id');

        $this->assertEquals('test-uuid-abc', $response->memo_uuid);
        $this->assertTrue($response->isProcessed());

        $request = $this->mockServer->getLastRequest();
        $this->assertStringContainsString('id_type=reference_id', $request['path']);
        $this->assertStringContainsString('/api/v1/memo/external-ref-123/status', $request['path']);
    }

    public function testCheckMemoStatusNotFound(): void
    {
        $mockErrorResponse = [
            'error' => 'Memo not found',
        ];

        $this->mockServer->queueResponse(404, $mockErrorResponse);

        $this->expectException(SkaldException::class);
        $this->expectExceptionMessageMatches('/Skald API error \(404\):/');

        $this->client->checkMemoStatus('non-existent-uuid');
    }

    public function testMemoFileDataToArray(): void
    {
        $fileData = new MemoFileData(
            title: 'Test Title',
            metadata: ['key' => 'value'],
            reference_id: 'ref123',
            tags: ['tag1', 'tag2'],
            source: 'test'
        );

        $array = $fileData->toArray();

        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertEquals('ref123', $array['reference_id']);
        $this->assertEquals(['tag1', 'tag2'], $array['tags']);
        $this->assertEquals('test', $array['source']);
    }

    public function testMemoFileDataToArrayWithDefaults(): void
    {
        $fileData = new MemoFileData();

        $array = $fileData->toArray();

        $this->assertEmpty($array);
        $this->assertArrayNotHasKey('title', $array);
        $this->assertArrayNotHasKey('metadata', $array);
        $this->assertArrayNotHasKey('reference_id', $array);
        $this->assertArrayNotHasKey('tags', $array);
        $this->assertArrayNotHasKey('source', $array);
    }
}
