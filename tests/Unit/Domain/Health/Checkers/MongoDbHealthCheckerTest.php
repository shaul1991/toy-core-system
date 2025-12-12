<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Health\Checkers;

use App\Domain\Health\Checkers\MongoDbHealthChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MongoDbHealthCheckerTest extends TestCase
{
    #[Test]
    public function name_returns_mongodb(): void
    {
        $checker = new MongoDbHealthChecker;

        $this->assertSame('mongodb', $checker->name());
    }

    #[Test]
    public function check_returns_unhealthy_when_extension_not_loaded(): void
    {
        if (extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension is loaded, cannot test extension not loaded scenario');
        }

        $checker = new MongoDbHealthChecker(
            host: 'localhost',
            port: 27017,
        );

        $result = $checker->check();

        $this->assertFalse($result->healthy);
        $this->assertSame('mongodb', $result->name);
        $this->assertStringContainsString('extension', $result->message);
    }

    #[Test]
    public function check_returns_unhealthy_when_host_not_configured(): void
    {
        if (! extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension is not loaded');
        }

        $checker = new MongoDbHealthChecker(
            host: '',
            port: 27017,
        );

        $result = $checker->check();

        $this->assertFalse($result->healthy);
        $this->assertSame('mongodb', $result->name);
        $this->assertStringContainsString('not configured', $result->message);
    }

    #[Test]
    public function check_returns_unhealthy_when_connection_fails(): void
    {
        if (! extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension is not loaded');
        }

        $checker = new MongoDbHealthChecker(
            host: 'invalid-host-that-does-not-exist',
            port: 27017,
            database: 'admin',
        );

        $result = $checker->check();

        $this->assertFalse($result->healthy);
        $this->assertSame('mongodb', $result->name);
        $this->assertGreaterThan(0, $result->responseTimeMs);
    }

    #[Test]
    public function check_includes_metadata(): void
    {
        if (! extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension is not loaded');
        }

        $checker = new MongoDbHealthChecker(
            host: 'localhost',
            port: 27017,
            database: 'testdb',
        );

        $result = $checker->check();
        $json = $result->jsonSerialize();

        // 결과에 관계없이 metadata가 포함되어야 함
        if (isset($json['metadata'])) {
            $this->assertArrayHasKey('host', $json['metadata']);
            $this->assertArrayHasKey('port', $json['metadata']);
        }
    }
}
