<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Health\Checkers;

use App\Domain\Health\Checkers\MinioHealthChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MinioHealthCheckerTest extends TestCase
{
    #[Test]
    public function name_returns_minio(): void
    {
        $checker = new MinioHealthChecker;

        $this->assertSame('minio', $checker->name());
    }

    #[Test]
    public function name_returns_minio_with_custom_disk(): void
    {
        $checker = new MinioHealthChecker(disk: 'minio-private');

        $this->assertSame('minio', $checker->name());
    }
}
