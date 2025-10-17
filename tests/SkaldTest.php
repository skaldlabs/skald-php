<?php

declare(strict_types=1);

namespace Skald\Tests;

use PHPUnit\Framework\TestCase;
use Skald\Exceptions\SkaldException;
use Skald\Skald;
use Skald\Types\ChatRequest;
use Skald\Types\GenerateDocRequest;
use Skald\Types\MemoData;
use Skald\Types\SearchMethod;
use Skald\Types\SearchRequest;

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
            project_id: 'proj123'
        );

        $array = $request->toArray(false);
        $this->assertEquals('Test query', $array['query']);
        $this->assertEquals('proj123', $array['project_id']);
        $this->assertFalse($array['stream']);

        $streamArray = $request->toArray(true);
        $this->assertTrue($streamArray['stream']);
    }

    public function testGenerateDocRequestToArray(): void
    {
        $request = new GenerateDocRequest(
            prompt: 'Test prompt',
            rules: 'Test rules',
            project_id: 'proj123'
        );

        $array = $request->toArray(false);
        $this->assertEquals('Test prompt', $array['prompt']);
        $this->assertEquals('Test rules', $array['rules']);
        $this->assertEquals('proj123', $array['project_id']);
        $this->assertFalse($array['stream']);
    }
}
