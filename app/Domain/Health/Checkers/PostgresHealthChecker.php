<?php

declare(strict_types=1);

namespace App\Domain\Health\Checkers;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PostgresHealthChecker implements HealthCheckerInterface
{
    public function __construct(
        private readonly string $connection = 'pgsql',
    ) {}

    public function name(): string
    {
        return 'postgres';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $pdo = DB::connection($this->connection)->getPdo();
            $result = DB::connection($this->connection)->selectOne('SELECT version()');

            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::healthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'Connection successful',
                metadata: [
                    'connection' => $this->connection,
                    'version' => $result->version ?? null,
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
