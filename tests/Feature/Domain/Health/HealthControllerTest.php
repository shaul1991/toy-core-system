<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Health;

use App\Domain\Health\Contracts\HealthCheckerInterface;
use App\Domain\Health\DTOs\HealthCheckResult;
use App\Domain\Health\Services\HealthCheckService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HealthControllerTest extends TestCase
{
    private HealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();

        // 테스트용 HealthCheckService 생성
        $this->healthCheckService = new HealthCheckService;
        $this->app->instance(HealthCheckService::class, $this->healthCheckService);
    }

    #[Test]
    public function index_returns_healthy_when_all_services_healthy(): void
    {
        $this->healthCheckService->register($this->createHealthyChecker('test1'));
        $this->healthCheckService->register($this->createHealthyChecker('test2'));

        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'total_response_time_ms',
                    'services',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'healthy');
    }

    #[Test]
    public function index_returns_service_unavailable_when_any_service_unhealthy(): void
    {
        $this->healthCheckService->register($this->createHealthyChecker('healthy'));
        $this->healthCheckService->register($this->createUnhealthyChecker('unhealthy'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'unhealthy');
    }

    #[Test]
    public function index_returns_empty_services_when_no_checkers_registered(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('data.status', 'healthy')
            ->assertJsonPath('data.services', []);
    }

    #[Test]
    public function show_returns_specific_service_health(): void
    {
        $this->healthCheckService->register($this->createHealthyChecker('postgres'));

        $response = $this->getJson('/api/health/postgres');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'name',
                    'status',
                    'response_time_ms',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'postgres')
            ->assertJsonPath('data.status', 'healthy');
    }

    #[Test]
    public function show_returns_service_unavailable_for_unhealthy_service(): void
    {
        $this->healthCheckService->register($this->createUnhealthyChecker('redis'));

        $response = $this->getJson('/api/health/redis');

        $response->assertStatus(503)
            ->assertJsonPath('data.name', 'redis')
            ->assertJsonPath('data.status', 'unhealthy');
    }

    #[Test]
    public function show_returns_not_found_for_unknown_service(): void
    {
        $this->healthCheckService->register($this->createHealthyChecker('postgres'));

        $response = $this->getJson('/api/health/unknown');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    #[Test]
    public function show_lists_available_services_in_not_found_message(): void
    {
        $this->healthCheckService->register($this->createHealthyChecker('postgres'));
        $this->healthCheckService->register($this->createHealthyChecker('redis'));

        $response = $this->getJson('/api/health/unknown');

        $response->assertNotFound();
        $this->assertStringContainsString('postgres', $response->json('error.message'));
        $this->assertStringContainsString('redis', $response->json('error.message'));
    }

    private function createHealthyChecker(string $name): HealthCheckerInterface
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('name')->willReturn($name);
        $checker->method('check')->willReturn(
            HealthCheckResult::healthy(
                name: $name,
                responseTimeMs: 1.0,
                message: 'OK',
            )
        );

        return $checker;
    }

    private function createUnhealthyChecker(string $name): HealthCheckerInterface
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('name')->willReturn($name);
        $checker->method('check')->willReturn(
            HealthCheckResult::unhealthy(
                name: $name,
                responseTimeMs: 1.0,
                message: 'Connection failed',
            )
        );

        return $checker;
    }
}
