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

echo "=== Skald PHP SDK - Document Generation Examples ===\n\n";

// Example 1: Generate a product requirements document
echo "1. Product Requirements Document\n";
echo str_repeat('=', 70) . "\n\n";

try {
    $response = $skald->generateDoc(new GenerateDocRequest(
        prompt: 'Create a product requirements document for our mobile app based on the planning meetings and customer feedback.',
        rules: 'Use formal business language. Include sections: Executive Summary, Requirements, Timeline, Success Metrics. Keep it under 500 words.'
    ));

    if ($response->ok) {
        echo $response->response;
        echo "\n\n";

        // Count citations
        preg_match_all('/\[\[(\d+)\]\]/', $response->response, $matches);
        if (!empty($matches[1])) {
            echo str_repeat('-', 70) . "\n";
            echo "📚 Document referenced " . count(array_unique($matches[1])) . " source(s)\n";
        }
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Example 2: Generate a technical specification
echo "2. Technical Specification\n";
echo str_repeat('=', 70) . "\n\n";

try {
    $response = $skald->generateDoc(new GenerateDocRequest(
        prompt: 'Write a technical specification for the API authentication system.',
        rules: 'Include sections: Overview, Architecture, Security Considerations, Implementation Details. Use technical language.'
    ));

    if ($response->ok) {
        echo $response->response;
        echo "\n\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Example 3: Generate a meeting summary report
echo "3. Meeting Summary Report\n";
echo str_repeat('=', 70) . "\n\n";

try {
    $response = $skald->generateDoc(new GenerateDocRequest(
        prompt: 'Create a comprehensive summary report of all Q1 planning meetings.',
        rules: <<<RULES
        Format:
        - Start with executive summary
        - Group by topic (Product, Engineering, Hiring, Budget)
        - Include key decisions and action items
        - Use bullet points for clarity
        - Keep professional tone
        RULES
    ));

    if ($response->ok) {
        echo $response->response;
        echo "\n\n";

        if (count($response->intermediate_steps) > 0) {
            echo str_repeat('-', 70) . "\n";
            echo "🔍 Agent performed " . count($response->intermediate_steps) . " intermediate steps\n";
        }
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Example 4: Generate customer-facing documentation
echo "4. Customer-Facing Documentation\n";
echo str_repeat('=', 70) . "\n\n";

try {
    $response = $skald->generateDoc(new GenerateDocRequest(
        prompt: 'Create user documentation for the mobile app features based on our product planning.',
        rules: <<<RULES
        Audience: End users (non-technical)
        Style: Friendly, clear, concise
        Include:
        - Feature overview
        - Step-by-step instructions
        - Common use cases
        Avoid: Technical jargon
        RULES
    ));

    if ($response->ok) {
        echo $response->response;
        echo "\n\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Example 5: Generate a status update
echo "5. Project Status Update\n";
echo str_repeat('=', 70) . "\n\n";

try {
    $response = $skald->generateDoc(new GenerateDocRequest(
        prompt: 'Write a status update on the mobile app development project for stakeholders.',
        rules: 'Concise format. Include: Current status, Completed items, In progress, Blockers, Next steps. Use clear headings.'
    ));

    if ($response->ok) {
        echo $response->response;
        echo "\n\n";

        // Show response statistics
        $wordCount = str_word_count($response->response);
        $lineCount = substr_count($response->response, "\n") + 1;

        echo str_repeat('-', 70) . "\n";
        echo "📊 Document Statistics:\n";
        echo "   - Words: {$wordCount}\n";
        echo "   - Lines: {$lineCount}\n";
    }
} catch (SkaldException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}
echo "\n";

echo "=== All document generation examples completed! ===\n";
echo "\nNote: Citations [[1]], [[2]], etc. link to source memos in your knowledge base.\n";
