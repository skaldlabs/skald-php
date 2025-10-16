<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\SearchRequest;
use Skald\Types\SearchMethod;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Search Examples ===\n\n";

// Example 1: Semantic vector search
echo "1. Semantic Vector Search\n";
echo str_repeat('-', 50) . "\n";
try {
    $results = $skald->search(new SearchRequest(
        query: 'quarterly goals and planning',
        searchMethod: SearchMethod::CHUNK_VECTOR_SEARCH,
        limit: 5
    ));

    echo "Found {$results->results} results\n\n";

    foreach ($results->results as $i => $result) {
        echo "Result " . ($i + 1) . ":\n";
        echo "  UUID: {$result->uuid}\n";
        echo "  Title: {$result->title}\n";
        echo "  Summary: {$result->summary}\n";
        echo "  Distance: " . ($result->distance !== null ? number_format($result->distance, 4) : 'N/A') . "\n";
        echo "  Snippet: " . substr($result->content_snippet, 0, 100) . "...\n\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 2: Search by title substring
echo "2. Title Contains Search\n";
echo str_repeat('-', 50) . "\n";
try {
    $results = $skald->search(new SearchRequest(
        query: 'meeting',
        searchMethod: SearchMethod::TITLE_CONTAINS,
        limit: 10
    ));

    echo "Found " . count($results->results) . " memos with 'meeting' in the title\n\n";

    foreach ($results->results as $i => $result) {
        echo ($i + 1) . ". {$result->title}\n";
        echo "   UUID: {$result->uuid}\n";
        echo "   Summary: " . substr($result->summary, 0, 80) . "...\n\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 3: Search by title prefix
echo "3. Title Starts With Search\n";
echo str_repeat('-', 50) . "\n";
try {
    $results = $skald->search(new SearchRequest(
        query: 'Q1',
        searchMethod: SearchMethod::TITLE_STARTSWITH,
        limit: 10
    ));

    echo "Found " . count($results->results) . " memos starting with 'Q1'\n\n";

    foreach ($results->results as $i => $result) {
        echo ($i + 1) . ". {$result->title}\n";
    }
    echo "\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}


echo "=== All search examples completed! ===\n";
