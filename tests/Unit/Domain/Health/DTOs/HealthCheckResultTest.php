<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Health\DTOs;

use App\Domain\Health\DTOs\HealthCheckResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HealthCheckResultTest extends TestCase
{
    #[Test]
    public function healthy_factory_creates_healthy_result(): void
    {
        $result = HealthCheckResult::healthy(
            name: 'postgres',
            responseTimeMs: 1.23,
            message: 'Connected',
            metadata: ['version' => '16.0'],
        );

        $this->assertTrue($result->healthy);
        $this->assertSame('postgres', $result->name);
        $this->assertSame(1.23, $result->responseTimeMs);
        $this->assertSame('Connected', $result->message);
        $this->assertSame(['version' => '16.0'], $result->metadata);
    }

    #[Test]
    public function unhealthy_factory_creates_unhealthy_result(): void
    {
        $result = HealthCheckResult::unhealthy(
            name: 'redis',
            responseTimeMs: 5.67,
            message: 'Connection refused',
            metadata: ['connection' => 'default'],
        );

        $this->assertFalse($result->healthy);
        $this->assertSame('redis', $result->name);
        $this->assertSame(5.67, $result->responseTimeMs);
        $this->assertSame('Connection refused', $result->message);
        $this->assertSame(['connection' => 'default'], $result->metadata);
    }

    #[Test]
    public function json_serialize_returns_correct_structure_for_healthy(): void
    {
        $result = HealthCheckResult::healthy(
            name: 'postgres',
            responseTimeMs: 1.234,
            message: 'OK',
            metadata: ['key' => 'value'],
        );

        $json = $result->jsonSerialize();

        $this->assertSame('postgres', $json['name']);
        $this->assertSame('healthy', $json['status']);
        $this->assertSame(1.23, $json['response_time_ms']);
        $this->assertSame('OK', $json['message']);
        $this->assertSame(['key' => 'value'], $json['metadata']);
    }

    #[Test]
    public function json_serialize_returns_correct_structure_for_unhealthy(): void
    {
        $result = HealthCheckResult::unhealthy(
            name: 'redis',
            responseTimeMs: 100.0,
            message: 'Timeout',
        );

        $json = $result->jsonSerialize();

        $this->assertSame('redis', $json['name']);
        $this->assertSame('unhealthy', $json['status']);
        $this->assertSame(100.0, $json['response_time_ms']);
        $this->assertSame('Timeout', $json['message']);
        $this->assertArrayNotHasKey('metadata', $json);
    }

    #[Test]
    public function json_serialize_omits_null_fields(): void
    {
        $result = HealthCheckResult::healthy(
            name: 'test',
            responseTimeMs: 0.5,
        );

        $json = $result->jsonSerialize();

        $this->assertArrayNotHasKey('message', $json);
        $this->assertArrayNotHasKey('metadata', $json);
    }

    #[Test]
    public function response_time_is_rounded_to_two_decimals(): void
    {
        $result = HealthCheckResult::healthy(
            name: 'test',
            responseTimeMs: 1.23456789,
        );

        $json = $result->jsonSerialize();

        $this->assertSame(1.23, $json['response_time_ms']);
    }
}
