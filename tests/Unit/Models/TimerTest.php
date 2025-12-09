<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Timer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Timer 모델 속성 및 Eloquent 기능 테스트
 * Laravel 컨테이너가 필요한 테스트
 */
class TimerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ========================================
    // 모델 속성 테스트
    // ========================================

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

    public function test_deleted_at_is_cast_to_datetime(): void
    {
        $timer = new Timer;
        $casts = $timer->getCasts();

        $this->assertArrayHasKey('deleted_at', $casts);
        $this->assertEquals('datetime', $casts['deleted_at']);
    }

    // ========================================
    // SoftDeletes 테스트
    // ========================================

    public function test_timer_uses_soft_deletes(): void
    {
        $timer = new Timer;

        $this->assertTrue(
            method_exists($timer, 'trashed'),
            'Timer should use SoftDeletes trait'
        );
    }

    public function test_timer_can_be_soft_deleted(): void
    {
        $timer = Timer::create([
            'key' => 'soft-delete-test',
            'target_at' => '2025-12-31 23:59:59',
        ]);

        $timer->delete();

        $this->assertSoftDeleted('timers', ['key' => 'soft-delete-test']);
        $this->assertNull(Timer::find($timer->id));
        $this->assertNotNull(Timer::withTrashed()->find($timer->id));
    }

    public function test_soft_deleted_timer_can_be_restored(): void
    {
        $timer = Timer::create([
            'key' => 'restore-test',
            'target_at' => '2025-12-31 23:59:59',
        ]);

        $timer->delete();
        $timer->restore();

        $this->assertNotSoftDeleted('timers', ['key' => 'restore-test']);
        $this->assertNotNull(Timer::find($timer->id));
    }

    // ========================================
    // Accessor 테스트
    // ========================================

    public function test_remaining_seconds_accessor_returns_negative_for_future_target(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $timer = Timer::create([
            'key' => 'accessor-future-test',
            'target_at' => '2025-01-01 01:00:00',
        ]);

        $this->assertEquals(-3600, $timer->remaining_seconds);
    }

    public function test_remaining_seconds_accessor_returns_positive_for_past_target(): void
    {
        Carbon::setTestNow('2025-01-01 02:00:00');

        $timer = Timer::create([
            'key' => 'accessor-past-test',
            'target_at' => '2025-01-01 01:00:00',
        ]);

        $this->assertEquals(3600, $timer->remaining_seconds);
    }

    // ========================================
    // 데이터베이스 테스트
    // ========================================

    public function test_timer_can_be_created_with_key_and_target_at(): void
    {
        $timer = Timer::create([
            'key' => 'create-test',
            'target_at' => '2025-12-31 23:59:59',
        ]);

        $this->assertDatabaseHas('timers', ['key' => 'create-test']);
        $this->assertEquals('create-test', $timer->key);
        $this->assertInstanceOf(Carbon::class, $timer->target_at);
    }

    public function test_timer_key_must_be_unique(): void
    {
        Timer::create([
            'key' => 'unique-key',
            'target_at' => '2025-12-31 23:59:59',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Timer::create([
            'key' => 'unique-key',
            'target_at' => '2025-06-15 12:00:00',
        ]);
    }
}
