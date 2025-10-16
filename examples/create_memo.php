<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\MemoData;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client with your API key
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Create Memo Examples ===\n\n";

try {
    $result = $skald->createMemo(new MemoData(
        title: 'Q1 2025 Planning Meeting Notes',
        content: <<<CONTENT
        # Q1 2025 Planning Meeting

        Date: January 15, 2025
        Attendees: Product Team, Engineering, Design

        ## Key Discussion Points

        1. **Revenue Targets**: Aiming for 40% growth in Q1
        2. **Hiring Plans**: Need to hire 3 engineers and 1 designer
        3. **Product Roadmap**:
           - Launch mobile app by March 1
           - Implement offline mode
           - Add push notifications
        4. **Budget Allocation**: Marketing budget increased by 25%

        ## Action Items
        - John: Draft job descriptions by Jan 20
        - Sarah: Create mobile app wireframes
        - Mike: Review budget proposal
        CONTENT,
        metadata: [
            'meeting_date' => '2025-01-15',
            'attendees' => ['John', 'Sarah', 'Mike', 'Lisa'],
            'duration_minutes' => 60,
            'location' => 'Conference Room A'
        ],
        reference_id: 'notion_page_abc123',
        tags: ['meeting', 'planning', 'q1-2025', 'product'],
        source: 'notion'
    ));

    echo "✓ Memo created successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}
