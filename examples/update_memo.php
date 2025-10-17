<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Types\UpdateMemoData;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client with your API key
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Update Memo Examples ===\n\n";

// You'll need to replace this with an actual memo UUID from your account
// You can create one using create_memo.php first
$memoId = '4db6bd34-7e64-4ee5-ab23-1c1e187931d6';

echo "Note: Replace '\$memoId' in this script with an actual memo UUID from your account.\n\n";

// Example 1: Update only the title
echo "Example 1: Update Only Title\n";
echo "------------------------------\n";
try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        title: 'Updated Meeting Notes - Q1 2025 Planning'
    ));

    echo "✓ Title updated successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 2: Update content (triggers reprocessing)
echo "Example 2: Update Content (Triggers Reprocessing)\n";
echo "--------------------------------------------------\n";
echo "When you update the content, the API automatically regenerates:\n";
echo "  - Summary\n";
echo "  - Tags\n";
echo "  - Chunks (for vector search)\n\n";

try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        content: <<<CONTENT
        # Q1 2025 Planning Meeting - UPDATED

        Date: January 15, 2025
        Attendees: Product Team, Engineering, Design, Marketing

        ## Key Discussion Points

        1. **Revenue Targets**: Revised to 50% growth in Q1 (increased from 40%)
        2. **Hiring Plans**: Expanded to 5 engineers, 2 designers, 1 product manager
        3. **Product Roadmap**:
           - Launch mobile app by February 15 (moved up from March 1)
           - Implement offline mode
           - Add push notifications
           - NEW: Dark mode support
           - NEW: Multi-language support
        4. **Budget Allocation**: Marketing budget increased by 35% (up from 25%)

        ## Action Items
        - John: Draft job descriptions by Jan 18 (accelerated)
        - Sarah: Create mobile app wireframes AND dark mode mockups
        - Mike: Review updated budget proposal
        - Lisa: Research internationalization requirements
        CONTENT
    ));

    echo "✓ Content updated successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n";
    echo "  Note: The memo has been automatically reprocessed.\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 3: Update multiple fields
echo "Example 3: Update Multiple Fields\n";
echo "----------------------------------\n";
try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        title: 'Q1 2025 Planning - Final Version',
        metadata: [
            'updated_at' => time(),
            'editor' => 'Jane Doe',
            'version' => '2.0',
            'status' => 'finalized'
        ],
        source: 'confluence',
        expiration_date: '2025-12-31T23:59:59Z'
    ));

    echo "✓ Multiple fields updated successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 4: Update only metadata (no reprocessing)
echo "Example 4: Update Only Metadata\n";
echo "--------------------------------\n";
echo "Updating metadata without content keeps existing summaries/tags intact.\n\n";

try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        metadata: [
            'last_viewed' => time(),
            'view_count' => 42,
            'favorite' => true
        ]
    ));

    echo "✓ Metadata updated successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n";
    echo "  Note: Existing summary and tags were preserved.\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 5: Update with client reference ID
echo "Example 5: Update Client Reference ID\n";
echo "--------------------------------------\n";
try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        client_reference_id: 'confluence_page_xyz789'
    ));

    echo "✓ Client reference ID updated successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 6: Update by client reference ID instead of UUID
echo "Example 6: Update by Reference ID\n";
echo "----------------------------------\n";
echo "You can update a memo using your own reference ID instead of the Skald UUID.\n\n";

$clientRefId = 'confluence_page_xyz789';

try {
    $result = $skald->updateMemo($clientRefId, new UpdateMemoData(
        title: 'Updated via Reference ID',
        metadata: ['updated_by_ref_id' => true]
    ), 'reference_id');

    echo "✓ Memo updated via reference ID successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 7: Update with project ID (Token Authentication)
echo "Example 7: Update with Project ID\n";
echo "----------------------------------\n";
echo "When using Token Authentication, you need to provide the project ID.\n\n";

try {
    $result = $skald->updateMemo($memoId, new UpdateMemoData(
        title: 'Updated with Project Context'
    ), 'memo_uuid', 'your-project-uuid-here');

    echo "✓ Memo updated with project ID successfully!\n";
    echo "  Status: " . ($result->ok ? 'OK' : 'FAILED') . "\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

echo "=== Examples Complete ===\n\n";
echo "Key Takeaways:\n";
echo "1. All fields are optional - update only what you need\n";
echo "2. Updating 'content' triggers automatic reprocessing\n";
echo "3. Updating other fields preserves existing AI-generated data\n";
echo "4. Perfect for incremental updates and metadata tracking\n";
echo "5. Use reference_id to update memos by your own IDs\n";
