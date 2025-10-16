# Skald PHP SDK Examples

This directory contains comprehensive examples demonstrating all features of the Skald PHP SDK.

## Prerequisites

1. Install dependencies:
   ```bash
   cd /path/to/skald-php
   composer install
   ```

2. Set your API key as an environment variable:
   ```bash
   export SKALD_API_KEY=sk_proj_your_api_key_here
   ```

## Running Examples

### Creating Memos

```bash
php examples/create_memo.php
```

Demonstrates:
- Creating memos with all fields
- Creating minimal memos
- Creating technical documentation memos
- Creating customer feedback memos

### Searching

```bash
php examples/search.php
```

Demonstrates:
- Semantic vector search
- Title substring search
- Title prefix search
- Tag filtering
- Finding most relevant results

### AI Chat

**Non-streaming:**
```bash
php examples/chat.php
```

Demonstrates:
- General questions
- Requesting summaries
- Specific queries
- Comparison questions
- Information extraction

**Streaming:**
```bash
php examples/chat_streaming.php
```

Demonstrates:
- Basic streaming chat
- Streaming with progress indicators
- Word-by-word processing
- Real-time analysis

### Document Generation

**Non-streaming:**
```bash
php examples/generate_doc.php
```

Demonstrates:
- Product requirements documents
- Technical specifications
- Meeting summaries
- Customer-facing documentation
- Status updates

**Streaming:**
```bash
php examples/generate_doc_streaming.php
```

Demonstrates:
- Basic streaming generation
- Live statistics during generation
- Progress indicators
- Long-form document generation

## Example Structure

Each example file:
- Checks for the SKALD_API_KEY environment variable
- Demonstrates multiple use cases
- Includes error handling
- Shows output formatting
- Provides helpful comments

## Common Patterns

### Error Handling
```php
try {
    $result = $skald->createMemo($memoData);
    echo "Success!\n";
} catch (SkaldException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

### Streaming
```php
$stream = $skald->streamedChat($request);
foreach ($stream as $event) {
    if ($event->isToken()) {
        echo $event->content;
    } elseif ($event->isDone()) {
        break;
    }
}
```

### Extracting Citations
```php
preg_match_all('/\[\[(\d+)\]\]/', $response->response, $matches);
$citations = array_unique($matches[1]);
```

## Tips

- **API Key**: Never hardcode your API key. Always use environment variables.
- **Error Handling**: Always wrap API calls in try-catch blocks.
- **Streaming**: Remember to call `flush()` when displaying streaming content.
- **Rate Limits**: Be mindful of API rate limits when running examples repeatedly.

## Need Help?

- Documentation: https://docs.useskald.com
- Support: support@useskald.com
- Issues: https://github.com/skald/skald-php/issues
