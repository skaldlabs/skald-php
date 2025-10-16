<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\GenerateDocRequest;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Streaming Document Generation ===\n\n";

echo "Generating product brief...\n\n";

try {
    $stream = $skald->streamedGenerateDoc(new GenerateDocRequest(
        prompt: 'Create a brief product overview for our mobile app.',
        rules: 'Keep it concise (under 200 words). Include key features and benefits.'
    ));

    $fullDocument = '';
    $tokenCount = 0;

    foreach ($stream as $event) {
        if ($event->isToken() && $event->content !== null) {
            echo $event->content;
            $fullDocument .= $event->content;
            $tokenCount++;
            flush();
        } elseif ($event->isDone()) {
            echo "\n\n";
            echo str_repeat('-', 70) . "\n";
            echo "✓ Generation complete ({$tokenCount} tokens)\n";
            break;
        }
    }
} catch (SkaldException $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
}
echo "\n";

