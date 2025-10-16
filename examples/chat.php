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

echo "=== Skald PHP SDK - Non-Streaming Chat Examples ===\n\n";

echo "Question: What are our main goals for Q1 2025?\n\n";

try {
    $response = $skald->chat(new ChatRequest(
        query: 'What are our main goals for Q1 2025?'
    ));

    if ($response->ok) {
        echo "Answer:\n";
        echo wordwrap($response->response, 70) . "\n\n";

        if (count($response->intermediate_steps) > 0) {
            echo "Debug Info: " . count($response->intermediate_steps) . " intermediate steps\n";
        }
    } else {
        echo "✗ Chat request was not successful\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";
