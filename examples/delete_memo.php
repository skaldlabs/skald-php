<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skald\Skald;
use Skald\Exceptions\SkaldException;

// Initialize the Skald client with your API key
$apiKey = getenv('SKALD_API_KEY');
if (!$apiKey) {
    die("Error: Please set SKALD_API_KEY environment variable\n");
}

$skald = new Skald($apiKey);

echo "=== Skald PHP SDK - Delete Memo Examples ===\n\n";

// You'll need to replace these with actual memo UUIDs or reference IDs from your account
// You can create memos using create_memo.php first
$memoUuid = 'your-memo-uuid-here';
$clientRefId = 'your-reference-id-here';
$projectId = 'your-project-uuid-here';

echo "Note: Replace the placeholder IDs in this script with actual values from your account.\n\n";

// Example 1: Delete by memo UUID (default)
echo "Example 1: Delete by Memo UUID\n";
echo "-------------------------------\n";
try {
    $skald->deleteMemo($memoUuid);
    echo "✓ Memo deleted successfully!\n";
    echo "  All associated data (content, summary, tags, chunks) has been removed.\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 2: Delete by client reference ID
echo "Example 2: Delete by Client Reference ID\n";
echo "-----------------------------------------\n";
echo "You can delete a memo using your own reference ID instead of the Skald UUID.\n\n";

try {
    $skald->deleteMemo($clientRefId, 'reference_id');
    echo "✓ Memo deleted successfully via reference ID!\n";
    echo "  The memo identified by '{$clientRefId}' has been removed.\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 3: Delete with project ID (Token Authentication)
echo "Example 3: Delete with Project ID\n";
echo "----------------------------------\n";
echo "When using Token Authentication, you need to provide the project ID.\n\n";

try {
    $skald->deleteMemo($memoUuid, 'memo_uuid', $projectId);
    echo "✓ Memo deleted successfully with project context!\n";
    echo "  Project ID: {$projectId}\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 4: Delete by reference ID with project ID
echo "Example 4: Delete by Reference ID with Project ID\n";
echo "--------------------------------------------------\n";
echo "Combining reference ID lookup with Token Authentication.\n\n";

try {
    $skald->deleteMemo($clientRefId, 'reference_id', $projectId);
    echo "✓ Memo deleted successfully!\n";
    echo "  Reference ID: {$clientRefId}\n";
    echo "  Project ID: {$projectId}\n\n";
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n\n";
}

// Example 5: Handling deletion errors
echo "Example 5: Handling Deletion Errors\n";
echo "------------------------------------\n";
echo "Demonstrating proper error handling for common scenarios.\n\n";

try {
    $skald->deleteMemo('non-existent-uuid');
    echo "✓ Memo deleted successfully!\n\n";
} catch (SkaldException $e) {
    $errorMessage = $e->getMessage();
    $httpCode = $e->getCode();

    if ($httpCode === 404) {
        echo "ℹ Memo not found (404)\n";
        echo "  This could mean:\n";
        echo "  - The memo was already deleted\n";
        echo "  - The ID is incorrect\n";
        echo "  - The memo belongs to a different project\n\n";
    } elseif ($httpCode === 403) {
        echo "⚠ Access denied (403)\n";
        echo "  The memo exists but you don't have permission to delete it.\n\n";
    } else {
        echo "✗ Error ({$httpCode}): {$errorMessage}\n\n";
    }
}

// Example 6: Invalid ID type
echo "Example 6: Invalid ID Type\n";
echo "--------------------------\n";

try {
    $skald->deleteMemo('some-id', 'invalid_type');
    echo "✓ Memo deleted successfully!\n\n";
} catch (SkaldException $e) {
    echo "✗ Expected error: {$e->getMessage()}\n";
    echo "  Only 'memo_uuid' and 'reference_id' are valid id_type values.\n\n";
}

echo "=== Examples Complete ===\n\n";
echo "Key Takeaways:\n";
echo "1. deleteMemo() removes the memo and all associated data permanently\n";
echo "2. Use 'memo_uuid' (default) to delete by Skald's UUID\n";
echo "3. Use 'reference_id' to delete by your own client reference ID\n";
echo "4. Deletion is permanent - there is no undo\n";
echo "5. Handle errors appropriately (404 = not found, 403 = access denied)\n";
echo "6. The method returns void on success (204 No Content)\n";
