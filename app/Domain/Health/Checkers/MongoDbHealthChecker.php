<?php

declare(strict_types=1);

namespace App\Domain\Health\Checkers;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Throwable;

final class MongoDbHealthChecker implements HealthCheckerInterface
{
    public function __construct(
        private readonly ?string $host = null,
        private readonly ?int $port = null,
        private readonly ?string $database = null,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {}

    public function name(): string
    {
        return 'mongodb';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        // MongoDB 확장 확인
        if (! extension_loaded('mongodb')) {
            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'MongoDB PHP extension is not installed',
            );
        }

        $host = $this->host ?: config('database.connections.mongodb.host', env('MONGODB_HOST', '127.0.0.1'));
        $port = $this->port ?: (int) config('database.connections.mongodb.port', env('MONGODB_PORT', 27017));
        $database = $this->database ?: config('database.connections.mongodb.database', env('MONGODB_DATABASE', 'admin'));
        $username = $this->username ?: config('database.connections.mongodb.username', env('MONGODB_USERNAME'));
        $password = $this->password ?: config('database.connections.mongodb.password', env('MONGODB_PASSWORD'));

        // 호스트가 비어있으면 설정되지 않은 것으로 간주
        if (empty($host)) {
            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'MongoDB host is not configured',
                metadata: [
                    'host' => $host,
                    'port' => $port,
                ],
            );
        }

        try {
            // MongoDB 연결 URI 생성
            $uri = $this->buildUri($host, $port, $username, $password);

            $manager = new Manager($uri);

            // ping 명령 실행
            $command = new Command(['ping' => 1]);
            $cursor = $manager->executeCommand($database, $command);
            $response = current($cursor->toArray());

            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            if (isset($response->ok) && $response->ok == 1) {
                return HealthCheckResult::healthy(
                    name: $this->name(),
                    responseTimeMs: $responseTimeMs,
                    message: 'Connection successful',
                    metadata: [
                        'host' => $host,
                        'port' => $port,
                        'database' => $database,
                    ],
                );
            }

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'Ping command failed',
                metadata: [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ],
            );
        } catch (Throwable $e) {
            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: $e->getMessage(),
                metadata: [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ],
            );
        }
    }

    private function buildUri(string $host, int $port, ?string $username, ?string $password): string
    {
        if ($username && $password) {
            $credentials = urlencode($username).':'.urlencode($password).'@';

            return "mongodb://{$credentials}{$host}:{$port}";
        }

        return "mongodb://{$host}:{$port}";
    }
}
