<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Health\Services;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use App\Domain\Health\Services\HealthCheckService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HealthCheckServiceTest extends TestCase
{
    private HealthCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HealthCheckService;
    }

    #[Test]
    public function register_adds_checker(): void
    {
        $checker = $this->createHealthyChecker('test');
        $this->service->register($checker);

        $this->assertContains('test', $this->service->getRegisteredServices());
    }

    #[Test]
    public function register_returns_self_for_chaining(): void
    {
        $checker = $this->createHealthyChecker('test');
        $result = $this->service->register($checker);

        $this->assertSame($this->service, $result);
    }

    #[Test]
    public function check_all_returns_healthy_when_all_checkers_healthy(): void
    {
        $this->service->register($this->createHealthyChecker('postgres'));
        $this->service->register($this->createHealthyChecker('redis'));

        $result = $this->service->checkAll();

        $this->assertSame('healthy', $result['status']);
        $this->assertCount(2, $result['services']);
        $this->assertArrayHasKey('total_response_time_ms', $result);
    }

    #[Test]
    public function check_all_returns_unhealthy_when_any_checker_unhealthy(): void
    {
        $this->service->register($this->createHealthyChecker('postgres'));
        $this->service->register($this->createUnhealthyChecker('redis'));

        $result = $this->service->checkAll();

        $this->assertSame('unhealthy', $result['status']);
        $this->assertCount(2, $result['services']);
    }

    #[Test]
    public function check_all_calculates_total_response_time(): void
    {
        $this->service->register($this->createHealthyChecker('postgres', 10.0));
        $this->service->register($this->createHealthyChecker('redis', 5.0));

        $result = $this->service->checkAll();

        $this->assertSame(15.0, $result['total_response_time_ms']);
    }

    #[Test]
    public function check_returns_result_for_registered_service(): void
    {
        $this->service->register($this->createHealthyChecker('postgres'));

        $result = $this->service->check('postgres');

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('postgres', $result->name);
    }

    #[Test]
    public function check_returns_null_for_unregistered_service(): void
    {
        $result = $this->service->check('unknown');

        $this->assertNull($result);
    }

    #[Test]
    public function get_registered_services_returns_empty_array_when_no_checkers(): void
    {
        $result = $this->service->getRegisteredServices();

        $this->assertSame([], $result);
    }

    #[Test]
    public function get_registered_services_returns_all_registered_names(): void
    {
        $this->service->register($this->createHealthyChecker('postgres'));
        $this->service->register($this->createHealthyChecker('redis'));
        $this->service->register($this->createHealthyChecker('mongodb'));

        $result = $this->service->getRegisteredServices();

        $this->assertCount(3, $result);
        $this->assertContains('postgres', $result);
        $this->assertContains('redis', $result);
        $this->assertContains('mongodb', $result);
    }

    private function createHealthyChecker(string $name, float $responseTime = 1.0): HealthCheckerInterface
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('name')->willReturn($name);
        $checker->method('check')->willReturn(
            HealthCheckResult::healthy(
                name: $name,
                responseTimeMs: $responseTime,
            )
        );

        return $checker;
    }

    private function createUnhealthyChecker(string $name, float $responseTime = 1.0): HealthCheckerInterface
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('name')->willReturn($name);
        $checker->method('check')->willReturn(
            HealthCheckResult::unhealthy(
                name: $name,
                responseTimeMs: $responseTime,
                message: 'Connection failed',
            )
        );

        return $checker;
    }
}
