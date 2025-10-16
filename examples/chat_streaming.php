<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\ChatRequest;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Streaming Chat Examples ===\n\n";

echo "Question: What are the key highlights from our planning meetings?\n\n";
echo "Answer (streaming): ";

try {
    $stream = $skald->streamedChat(new ChatRequest(
        query: 'What are the key highlights from our planning meetings?'
    ));

    $fullResponse = '';

    foreach ($stream as $event) {
        if ($event->isToken() && $event->content !== null) {
            echo $event->content;
            $fullResponse .= $event->content;
            flush(); // Ensure output is sent immediately
        } elseif ($event->isDone()) {
            echo "\n\n";
            echo "✓ Streaming complete\n";
            break;
        }
    }
} catch (SkaldException $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
}
