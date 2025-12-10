<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Timer;
use Illuminate\Support\Carbon;

class EloquentTimerRepository implements TimerRepositoryInterface
{
    /**
     * 키로 타이머 조회
     */
    public function findByKey(string $key): ?Timer
    {
        return Timer::where('key', $key)->first();
    }

    /**
     * 키로 타이머 조회 (삭제된 것 포함)
     */
    public function findByKeyWithTrashed(string $key): ?Timer
    {
        return Timer::withTrashed()->where('key', $key)->first();
    }

    /**
     * 타이머 생성
     */
    public function create(string $key, string $targetAt): Timer
    {
        return Timer::create([
            'key' => $key,
            'target_at' => $this->parseToUtc($targetAt),
        ]);
    }

    /**
     * 타이머 업데이트
     */
    public function update(Timer $timer, string $targetAt): Timer
    {
        $timer->update(['target_at' => $this->parseToUtc($targetAt)]);
        $timer->refresh();

        return $timer;
    }

    /**
     * datetime 문자열을 파싱하여 UTC로 변환
     */
    private function parseToUtc(string $datetime): Carbon
    {
        return Carbon::parse($datetime)->utc();
    }

    /**
     * 타이머 삭제 (soft delete)
     */
    public function delete(Timer $timer): bool
    {
        return $timer->delete();
    }

    /**
     * 삭제된 타이머 복원
     */
    public function restore(Timer $timer): bool
    {
        return $timer->restore();
    }
}
