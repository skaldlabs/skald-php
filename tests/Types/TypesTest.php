<?php

declare(strict_types=1);

namespace Skald\Tests\Types;

use PHPUnit\Framework\TestCase;
use Skald\Types\ChatResponse;
use Skald\Types\ChatStreamEvent;
use Skald\Types\CreateMemoResponse;
use Skald\Types\GenerateDocResponse;
use Skald\Types\GenerateDocStreamEvent;
use Skald\Types\SearchResult;
use Skald\Types\SearchResponse;

/**
 * Unit tests for type classes.
 */
class TypesTest extends TestCase
{
    public function testCreateMemoResponseFromArray(): void
    {
        $data = ['ok' => true];
        $response = CreateMemoResponse::fromArray($data);

        $this->assertTrue($response->ok);
    }

    public function testSearchResultFromArray(): void
    {
        $data = [
            'uuid' => 'test-uuid-123',
            'title' => 'Test Title',
            'summary' => 'Test summary',
            'content_snippet' => 'Test snippet',
            'distance' => 0.5,
        ];

        $result = SearchResult::fromArray($data);

        $this->assertEquals('test-uuid-123', $result->uuid);
        $this->assertEquals('Test Title', $result->title);
        $this->assertEquals('Test summary', $result->summary);
        $this->assertEquals('Test snippet', $result->content_snippet);
        $this->assertEquals(0.5, $result->distance);
    }

    public function testSearchResultFromArrayWithNullDistance(): void
    {
        $data = [
            'uuid' => 'test-uuid-123',
            'title' => 'Test Title',
            'summary' => 'Test summary',
            'content_snippet' => 'Test snippet',
            'distance' => null,
        ];

        $result = SearchResult::fromArray($data);

        $this->assertNull($result->distance);
    }

    public function testSearchResponseFromArray(): void
    {
        $data = [
            'results' => [
                [
                    'uuid' => 'uuid1',
                    'title' => 'Title 1',
                    'summary' => 'Summary 1',
                    'content_snippet' => 'Snippet 1',
                    'distance' => 0.3,
                ],
                [
                    'uuid' => 'uuid2',
                    'title' => 'Title 2',
                    'summary' => 'Summary 2',
                    'content_snippet' => 'Snippet 2',
                    'distance' => null,
                ],
            ],
        ];

        $response = SearchResponse::fromArray($data);

        $this->assertCount(2, $response->results);
        $this->assertEquals('uuid1', $response->results[0]->uuid);
        $this->assertEquals('uuid2', $response->results[1]->uuid);
    }

    public function testChatResponseFromArray(): void
    {
        $data = [
            'ok' => true,
            'response' => 'Test response [[1]]',
            'intermediate_steps' => [
                ['step' => 1, 'action' => 'search'],
            ],
        ];

        $response = ChatResponse::fromArray($data);

        $this->assertTrue($response->ok);
        $this->assertEquals('Test response [[1]]', $response->response);
        $this->assertCount(1, $response->intermediate_steps);
    }

    public function testChatStreamEventFromArray(): void
    {
        $tokenEvent = ChatStreamEvent::fromArray([
            'type' => 'token',
            'content' => 'Hello',
        ]);

        $this->assertEquals('token', $tokenEvent->type);
        $this->assertEquals('Hello', $tokenEvent->content);
        $this->assertTrue($tokenEvent->isToken());
        $this->assertFalse($tokenEvent->isDone());

        $doneEvent = ChatStreamEvent::fromArray([
            'type' => 'done',
        ]);

        $this->assertEquals('done', $doneEvent->type);
        $this->assertNull($doneEvent->content);
        $this->assertFalse($doneEvent->isToken());
        $this->assertTrue($doneEvent->isDone());
    }

    public function testGenerateDocResponseFromArray(): void
    {
        $data = [
            'ok' => true,
            'response' => 'Generated doc [[1]] content [[2]]',
            'intermediate_steps' => [],
        ];

        $response = GenerateDocResponse::fromArray($data);

        $this->assertTrue($response->ok);
        $this->assertEquals('Generated doc [[1]] content [[2]]', $response->response);
        $this->assertIsArray($response->intermediate_steps);
    }

    public function testGenerateDocStreamEventFromArray(): void
    {
        $tokenEvent = GenerateDocStreamEvent::fromArray([
            'type' => 'token',
            'content' => 'Generated',
        ]);

        $this->assertEquals('token', $tokenEvent->type);
        $this->assertEquals('Generated', $tokenEvent->content);
        $this->assertTrue($tokenEvent->isToken());
        $this->assertFalse($tokenEvent->isDone());

        $doneEvent = GenerateDocStreamEvent::fromArray([
            'type' => 'done',
        ]);

        $this->assertTrue($doneEvent->isDone());
    }
}
