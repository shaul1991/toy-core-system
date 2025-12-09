<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Timer;
use App\Repositories\TimerRepositoryInterface;
use App\Services\TimerService;
use App\Shared\Exceptions\NotFoundException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TimerServiceTest extends TestCase
{
    private MockInterface $timerRepository;

    private TimerService $timerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timerRepository = Mockery::mock(TimerRepositoryInterface::class);
        $this->timerService = new TimerService($this->timerRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // getTimer 테스트
    // ========================================

    public function test_get_timer_returns_timer_when_found(): void
    {
        $timer = $this->createTimerMock('test-key');

        $this->timerRepository
            ->shouldReceive('findByKey')
            ->with('test-key')
            ->once()
            ->andReturn($timer);

        $result = $this->timerService->getTimer('test-key');

        $this->assertSame($timer, $result);
    }

    public function test_get_timer_throws_not_found_exception_when_not_found(): void
    {
        $this->timerRepository
            ->shouldReceive('findByKey')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Timer(을)를 찾을 수 없습니다: nonexistent');

        $this->timerService->getTimer('nonexistent');
    }

    // ========================================
    // upsertTimer 테스트
    // ========================================

    public function test_upsert_timer_creates_new_timer_when_not_exists(): void
    {
        $newTimer = $this->createTimerMock('new-key');

        $this->timerRepository
            ->shouldReceive('findByKeyWithTrashed')
            ->with('new-key')
            ->once()
            ->andReturn(null);

        $this->timerRepository
            ->shouldReceive('create')
            ->with('new-key', '2025-12-31 23:59:59')
            ->once()
            ->andReturn($newTimer);

        $result = $this->timerService->upsertTimer('new-key', '2025-12-31 23:59:59');

        $this->assertSame($newTimer, $result);
    }

    public function test_upsert_timer_updates_existing_timer(): void
    {
        $existingTimer = $this->createTimerMock('existing-key', false);
        $updatedTimer = $this->createTimerMock('existing-key');

        $this->timerRepository
            ->shouldReceive('findByKeyWithTrashed')
            ->with('existing-key')
            ->once()
            ->andReturn($existingTimer);

        $this->timerRepository
            ->shouldReceive('update')
            ->with($existingTimer, '2025-12-31 23:59:59')
            ->once()
            ->andReturn($updatedTimer);

        $result = $this->timerService->upsertTimer('existing-key', '2025-12-31 23:59:59');

        $this->assertSame($updatedTimer, $result);
    }

    public function test_upsert_timer_restores_and_updates_soft_deleted_timer(): void
    {
        $trashedTimer = $this->createTimerMock('trashed-key', true);
        $restoredTimer = $this->createTimerMock('trashed-key');

        $this->timerRepository
            ->shouldReceive('findByKeyWithTrashed')
            ->with('trashed-key')
            ->once()
            ->andReturn($trashedTimer);

        $this->timerRepository
            ->shouldReceive('restore')
            ->with($trashedTimer)
            ->once()
            ->andReturn(true);

        $this->timerRepository
            ->shouldReceive('update')
            ->with($trashedTimer, '2025-12-31 23:59:59')
            ->once()
            ->andReturn($restoredTimer);

        $result = $this->timerService->upsertTimer('trashed-key', '2025-12-31 23:59:59');

        $this->assertSame($restoredTimer, $result);
    }

    // ========================================
    // deleteTimer 테스트
    // ========================================

    public function test_delete_timer_soft_deletes_and_returns_timer(): void
    {
        $timer = $this->createTimerMock('delete-key');

        $this->timerRepository
            ->shouldReceive('findByKey')
            ->with('delete-key')
            ->once()
            ->andReturn($timer);

        $this->timerRepository
            ->shouldReceive('delete')
            ->with($timer)
            ->once()
            ->andReturn(true);

        $timer->shouldReceive('refresh')
            ->once()
            ->andReturnSelf();

        $result = $this->timerService->deleteTimer('delete-key');

        $this->assertSame($timer, $result);
    }

    public function test_delete_timer_throws_not_found_exception_when_not_found(): void
    {
        $this->timerRepository
            ->shouldReceive('findByKey')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Timer(을)를 찾을 수 없습니다: nonexistent');

        $this->timerService->deleteTimer('nonexistent');
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function createTimerMock(string $key, ?bool $trashed = null): MockInterface
    {
        $timer = Mockery::mock(Timer::class);
        $timer->shouldReceive('getAttribute')
            ->with('key')
            ->andReturn($key);

        if ($trashed !== null) {
            $timer->shouldReceive('trashed')
                ->andReturn($trashed);
        }

        return $timer;
    }
}
