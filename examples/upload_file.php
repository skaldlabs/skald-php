<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\MemoFileData;

// Initialize the Skald client with your API key
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    echo "Error: SKALD_API_KEY environment variable not set\n";
    exit(1);
}

$skald = new Skald($apiKey);

try {
    // Path to the file you want to upload (PDF, DOC, DOCX, PPTX up to 100MB)
    $filePath = __DIR__ . '/sample-document.pdf';

    if (!file_exists($filePath)) {
        echo "Error: File not found at {$filePath}\n";
        echo "Please provide a valid file path.\n";
        exit(1);
    }

    echo "Uploading file: {$filePath}\n";

    // Create memo from file with optional metadata
    $memoData = new MemoFileData(
        title: 'Q1 2025 Planning Document',
        metadata: [
            'department' => 'Engineering',
            'quarter' => 'Q1',
            'year' => 2025,
        ],
        tags: ['planning', 'quarterly', 'engineering'],
        source: 'google-drive',
        reference_id: 'gdrive-doc-12345'
    );

    $response = $skald->createMemoFromFile($filePath, $memoData);

    if ($response->ok) {
        echo "✓ File uploaded successfully!\n";
        echo "The document is now being processed and will be available for search and chat soon.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
