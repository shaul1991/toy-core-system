<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Timer;

interface TimerRepositoryInterface
{
    /**
     * 키로 타이머 조회
     */
    public function findByKey(string $key): ?Timer;

    /**
     * 키로 타이머 조회 (삭제된 것 포함)
     */
    public function findByKeyWithTrashed(string $key): ?Timer;

    /**
     * 타이머 생성
     */
    public function create(string $key, string $targetAt): Timer;

    /**
     * 타이머 업데이트
     */
    public function update(Timer $timer, string $targetAt): Timer;

    /**
     * 타이머 삭제 (soft delete)
     */
    public function delete(Timer $timer): bool;

    /**
     * 삭제된 타이머 복원
     */
    public function restore(Timer $timer): bool;
}
