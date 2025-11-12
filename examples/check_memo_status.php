<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;

// Initialize the Skald client with your API key
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    echo "Error: SKALD_API_KEY environment variable not set\n";
    exit(1);
}

$skald = new Skald($apiKey);

try {
    // Check status using memo UUID
    $memoId = 'your-memo-uuid-here';

    echo "Checking status of memo: {$memoId}\n";

    $status = $skald->checkMemoStatus($memoId);

    echo "Memo UUID: {$status->memo_uuid}\n";
    echo "Status: {$status->status}\n";

    if ($status->isProcessing()) {
        echo "✓ The memo is currently being processed.\n";
    } elseif ($status->isProcessed()) {
        echo "✓ The memo has been successfully processed and is ready for search and chat.\n";
    } elseif ($status->isError()) {
        echo "✗ The memo processing failed.\n";
        if ($status->error_reason) {
            echo "Error reason: {$status->error_reason}\n";
        }
    }

    echo "\n--- Example: Polling until processing is complete ---\n";

    // Poll until processing is complete
    $maxAttempts = 30; // 30 attempts × 2 seconds = 1 minute max
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $status = $skald->checkMemoStatus($memoId);

        if ($status->isProcessed()) {
            echo "✓ Processing complete!\n";
            break;
        } elseif ($status->isError()) {
            echo "✗ Processing failed: {$status->error_reason}\n";
            break;
        }

        echo "Still processing... (attempt {$attempt}/{$maxAttempts})\n";
        sleep(2); // Wait 2 seconds before checking again
        $attempt++;
    }

    if ($attempt >= $maxAttempts) {
        echo "⚠ Timeout: Processing is taking longer than expected.\n";
    }

    echo "\n--- Example: Check status using reference ID ---\n";

    // Check status using client reference ID instead of memo UUID
    $referenceId = 'your-reference-id-here';
    $status = $skald->checkMemoStatus($referenceId, 'reference_id');

    echo "Status by reference ID ({$referenceId}): {$status->status}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
