<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Timer;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class TimerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_remaining_seconds_is_negative_when_target_is_in_future(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $targetAt = Carbon::parse('2025-01-01 01:00:00'); // 1 hour later

        // Target is in future, remaining_seconds should be negative
        $this->assertEquals(-3600, Timer::calculateRemainingSeconds($targetAt));
    }

    public function test_remaining_seconds_is_positive_when_target_is_in_past(): void
    {
        Carbon::setTestNow('2025-01-01 02:00:00');

        $targetAt = Carbon::parse('2025-01-01 01:00:00'); // 1 hour ago

        // Target is in past, remaining_seconds should be positive
        $this->assertEquals(3600, Timer::calculateRemainingSeconds($targetAt));
    }

    public function test_remaining_seconds_is_zero_when_target_is_now(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $targetAt = Carbon::parse('2025-01-01 00:00:00');

        $this->assertEquals(0, Timer::calculateRemainingSeconds($targetAt));
    }

    public function test_remaining_seconds_calculation_with_seconds_precision(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $targetAt = Carbon::parse('2025-01-01 00:00:30'); // 30 seconds later

        $this->assertEquals(-30, Timer::calculateRemainingSeconds($targetAt));
    }

    public function test_remaining_seconds_with_large_time_difference(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $targetAt = Carbon::parse('2025-12-31 23:59:59'); // ~365 days later

        $result = Timer::calculateRemainingSeconds($targetAt);

        // Should be approximately -31535999 seconds (negative = time remaining)
        $this->assertLessThan(0, $result);
        $this->assertLessThan(-31000000, $result);
    }

    public function test_fillable_attributes(): void
    {
        $timer = new Timer;

        $this->assertEquals(['key', 'target_at'], $timer->getFillable());
    }

    public function test_target_at_is_cast_to_datetime(): void
    {
        $timer = new Timer;
        $casts = $timer->getCasts();

        $this->assertArrayHasKey('target_at', $casts);
        $this->assertEquals('datetime', $casts['target_at']);
    }
}
