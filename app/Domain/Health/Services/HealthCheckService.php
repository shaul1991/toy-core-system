<?php

declare(strict_types=1);

namespace App\Domain\Health\Services;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\NotFoundException;

final class HealthCheckService
{
    /**
     * @var array<string, HealthCheckerInterface>
     */
    private array $checkers = [];

    /**
     * Health Checker 등록
     */
    public function register(HealthCheckerInterface $checker): self
    {
        if (array_key_exists($checker->name(), $this->checkers)) {
            throw ConflictException::duplicateField('name', $checker->name());
        }

        $this->checkers[$checker->name()] = $checker;

        return $this;
    }

    /**
     * 모든 Health Check 수행
     *
     * @return array{
     *     status: string,
     *     total_response_time_ms: float,
     *     services: array<HealthCheckResult>
     * }
     */
    public function checkAll(): array
    {
        $results = [];
        $allHealthy = true;
        $totalResponseTime = 0.0;

        foreach ($this->checkers as $checker) {
            $result = $checker->check();
            $results[] = $result;
            $totalResponseTime += $result->responseTimeMs;

            if (! $result->healthy) {
                $allHealthy = false;
            }
        }

        return [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'total_response_time_ms' => round($totalResponseTime, 2),
            'services' => $results,
        ];
    }

    /**
     * 특정 서비스 Health Check 수행
     */
    public function check(string $name): HealthCheckResult
    {
        if (! isset($this->checkers[$name])) {
            $available = implode(', ', $this->getRegisteredServices());
            throw NotFoundException::withMessage(
                "HealthChecker '{$name}' not found. Available services: {$available}"
            )->withDetails(['available_services' => $this->getRegisteredServices()]);
        }

        return $this->checkers[$name]->check();
    }

    /**
     * 등록된 서비스 목록 반환
     *
     * @return array<string>
     */
    public function getRegisteredServices(): array
    {
        return array_keys($this->checkers);
    }
}
