<?php

declare(strict_types=1);

namespace Skald\Tests;

/**
 * Simple mock HTTP server using PHP's built-in server.
 *
 * This creates a temporary PHP script that acts as a mock server
 * and runs it using PHP's built-in web server.
 */
class MockHttpServer
{
    private static ?int $port = null;
    private static $process = null;
    private static string $tempDir;
    private static string $serverScript;
    private array $responseQueue = [];
    private array $requests = [];

    public function __construct()
    {
        if (self::$port === null) {
            self::$port = $this->findAvailablePort();
            self::$tempDir = sys_get_temp_dir() . '/skald-mock-server-' . uniqid();
            @mkdir(self::$tempDir, 0777, true);
        }
    }

    /**
     * Find an available port.
     */
    private function findAvailablePort(): int
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return 18080; // Fallback
        }

        @socket_bind($socket, '127.0.0.1', 0);
        @socket_getsockname($socket, $addr, $port);
        @socket_close($socket);

        return $port ?: 18080;
    }

    public function start(): void
    {
        $this->createServerScript();
        $this->startPhpServer();
    }

    public function stop(): void
    {
        if (self::$process !== null && is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$process = null;
        }
    }

    public function getBaseUrl(): string
    {
        return 'http://127.0.0.1:' . self::$port;
    }

    private function createServerScript(): void
    {
        $responsesFile = self::$tempDir . '/responses.json';
        $requestsFile = self::$tempDir . '/requests.json';

        // Initialize files
        file_put_contents($responsesFile, json_encode([]));
        file_put_contents($requestsFile, json_encode([]));

        $script = <<<'PHP'
<?php
$responsesFile = __DIR__ . '/responses.json';
$requestsFile = __DIR__ . '/requests.json';

// Store request
$request = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
];

$requests = json_decode(file_get_contents($requestsFile), true) ?: [];
$requests[] = $request;
file_put_contents($requestsFile, json_encode($requests));

// Get response
$responses = json_decode(file_get_contents($responsesFile), true) ?: [];
$response = array_shift($responses) ?: ['status' => 404, 'data' => ['error' => 'No response queued'], 'stream' => false];
file_put_contents($responsesFile, json_encode($responses));

http_response_code($response['status']);

if ($response['stream']) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    foreach ($response['data'] as $chunk) {
        echo $chunk;
        flush();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode($response['data']);
}
PHP;

        self::$serverScript = self::$tempDir . '/server.php';
        file_put_contents(self::$serverScript, $script);
    }

    private function startPhpServer(): void
    {
        if (self::$process !== null) {
            return;
        }

        $command = sprintf(
            'php -S 127.0.0.1:%d -t %s %s > /dev/null 2>&1 & echo $!',
            self::$port,
            escapeshellarg(self::$tempDir),
            escapeshellarg(self::$serverScript)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        self::$process = proc_open(
            sprintf('php -S 127.0.0.1:%d %s', self::$port, escapeshellarg(self::$serverScript)),
            $descriptors,
            $pipes,
            self::$tempDir
        );

        // Wait for server to start
        usleep(500000); // 500ms
    }

    public function queueResponse(int $statusCode, array $data): void
    {
        $responsesFile = self::$tempDir . '/responses.json';
        $responses = json_decode(@file_get_contents($responsesFile), true) ?: [];
        $responses[] = [
            'status' => $statusCode,
            'data' => $data,
            'stream' => false,
        ];
        file_put_contents($responsesFile, json_encode($responses));
    }

    public function queueStreamResponse(int $statusCode, array $streamData): void
    {
        $responsesFile = self::$tempDir . '/responses.json';
        $responses = json_decode(@file_get_contents($responsesFile), true) ?: [];
        $responses[] = [
            'status' => $statusCode,
            'data' => $streamData,
            'stream' => true,
        ];
        file_put_contents($responsesFile, json_encode($responses));
    }

    public function getLastRequest(): ?array
    {
        $requestsFile = self::$tempDir . '/requests.json';
        $requests = json_decode(@file_get_contents($requestsFile), true) ?: [];
        return end($requests) ?: null;
    }

    public function getAllRequests(): array
    {
        $requestsFile = self::$tempDir . '/requests.json';
        return json_decode(@file_get_contents($requestsFile), true) ?: [];
    }

    public function __destruct()
    {
        $this->stop();
    }
}
