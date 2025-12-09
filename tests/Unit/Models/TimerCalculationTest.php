<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Timer;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Timer::calculateRemainingSeconds() 순수 계산 로직 테스트
 * Laravel 컨테이너 없이 실행 가능한 단위 테스트
 */
class TimerCalculationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ========================================
    // calculateRemainingSeconds 테스트 (DataProvider)
    // ========================================

    #[DataProvider('remainingSecondsProvider')]
    public function test_calculate_remaining_seconds(
        string $now,
        string $targetAt,
        int $expectedSeconds,
        string $description
    ): void {
        Carbon::setTestNow($now);

        $result = Timer::calculateRemainingSeconds(Carbon::parse($targetAt));

        $this->assertEquals($expectedSeconds, $result, $description);
    }

    public static function remainingSecondsProvider(): array
    {
        return [
            // [현재 시간, 목표 시간, 예상 결과, 설명]
            'target_1_hour_in_future' => [
                '2025-01-01 00:00:00',
                '2025-01-01 01:00:00',
                -3600,
                '1시간 후 목표 → -3600초 (남음)',
            ],
            'target_1_hour_in_past' => [
                '2025-01-01 02:00:00',
                '2025-01-01 01:00:00',
                3600,
                '1시간 전 목표 → +3600초 (지남)',
            ],
            'target_is_now' => [
                '2025-01-01 00:00:00',
                '2025-01-01 00:00:00',
                0,
                '현재와 동일 → 0초',
            ],
            'target_30_seconds_in_future' => [
                '2025-01-01 00:00:00',
                '2025-01-01 00:00:30',
                -30,
                '30초 후 목표 → -30초 (남음)',
            ],
            'target_30_seconds_in_past' => [
                '2025-01-01 00:00:30',
                '2025-01-01 00:00:00',
                30,
                '30초 전 목표 → +30초 (지남)',
            ],
            'target_1_minute_in_future' => [
                '2025-01-01 00:00:00',
                '2025-01-01 00:01:00',
                -60,
                '1분 후 목표 → -60초 (남음)',
            ],
            'target_1_day_in_future' => [
                '2025-01-01 00:00:00',
                '2025-01-02 00:00:00',
                -86400,
                '1일 후 목표 → -86400초 (남음)',
            ],
            'target_1_day_in_past' => [
                '2025-01-02 00:00:00',
                '2025-01-01 00:00:00',
                86400,
                '1일 전 목표 → +86400초 (지남)',
            ],
            'target_1_week_in_future' => [
                '2025-01-01 00:00:00',
                '2025-01-08 00:00:00',
                -604800,
                '1주 후 목표 → -604800초 (남음)',
            ],
        ];
    }

    // ========================================
    // 경계값 테스트
    // ========================================

    public function test_remaining_seconds_with_large_time_difference(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $targetAt = Carbon::parse('2025-12-31 23:59:59'); // ~365 days later

        $result = Timer::calculateRemainingSeconds($targetAt);

        // 약 31,535,999초 (365일 - 1초)
        $this->assertLessThan(0, $result);
        $this->assertLessThan(-31000000, $result);
        $this->assertGreaterThan(-32000000, $result);
    }

    public function test_remaining_seconds_with_milliseconds_ignored(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00.500');

        $targetAt = Carbon::parse('2025-01-01 00:00:01.500');

        $result = Timer::calculateRemainingSeconds($targetAt);

        // 밀리초는 무시되고 초 단위로 계산
        $this->assertEquals(-1, $result);
    }
}
