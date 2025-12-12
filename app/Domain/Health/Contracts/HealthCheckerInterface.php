<?php

declare(strict_types=1);

namespace App\Domain\Health\Contracts;

use App\Domain\Health\DTOs\HealthCheckResult;

/**
 * Health Checker 인터페이스
 *
 * 새로운 서비스(MongoDB, Kafka 등)의 Health Check를 추가할 때
 * 이 인터페이스를 구현하면 됩니다.
 */
interface HealthCheckerInterface
{
    /**
     * 서비스 이름 반환
     */
    public function name(): string;

    /**
     * Health Check 수행
     */
    public function check(): HealthCheckResult;
}
