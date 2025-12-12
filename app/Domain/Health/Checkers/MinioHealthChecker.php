<?php

declare(strict_types=1);

namespace App\Domain\Health\Checkers;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class MinioHealthChecker implements HealthCheckerInterface
{
    public function __construct(
        private readonly string $disk = 'minio-public',
    ) {}

    public function name(): string
    {
        return 'minio';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $storage = Storage::disk($this->disk);

            // 버킷 존재 확인 (디렉토리 목록 조회)
            $storage->directories('/');

            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::healthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: 'Connection successful',
                metadata: [
                    'disk' => $this->disk,
                ],
            );
        } catch (Throwable $e) {
            $responseTimeMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::unhealthy(
                name: $this->name(),
                responseTimeMs: $responseTimeMs,
                message: $e->getMessage(),
                metadata: [
                    'disk' => $this->disk,
                ],
            );
        }
    }
}
