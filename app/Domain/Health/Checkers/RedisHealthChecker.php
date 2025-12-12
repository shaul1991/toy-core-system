<?php

declare(strict_types=1);

namespace App\Domain\Health\Checkers;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RedisHealthChecker implements HealthCheckerInterface
{
    public function __construct(
        private readonly string $connection = 'default',
    ) {}

    public function name(): string
    {
        return 'redis';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $redis = Redis::connection($this->connection);
            $pong = $redis->ping();

            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            // ping() 결과는 Redis 드라이버에 따라 다를 수 있음
            $isHealthy = $pong === true || $pong === 'PONG' || $pong === '+PONG';

            if ($isHealthy) {
                return HealthCheckResult::healthy(
                    name: $this->name(),
                    responseTimeMs: $responseTimeMs,
                    message: 'Connection successful',
                    metadata: [
                        'connection' => $this->connection,
                    ],
                );
            }

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'Unexpected ping response',
                metadata: [
                    'connection' => $this->connection,
                    'response' => $pong,
                ],
            );
        } catch (Throwable $e) {
            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: $e->getMessage(),
                metadata: [
                    'connection' => $this->connection,
                ],
            );
        }
    }
}
