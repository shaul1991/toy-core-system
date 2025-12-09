<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Timer;
use App\Repositories\TimerRepositoryInterface;
use App\Shared\Exceptions\NotFoundException;

class TimerService
{
    public function __construct(
        private TimerRepositoryInterface $timerRepository
    ) {}

    /**
     * 타이머 조회
     *
     * @throws NotFoundException
     */
    public function getTimer(string $key): Timer
    {
        $timer = $this->timerRepository->findByKey($key);

        if (! $timer) {
            throw NotFoundException::forResource('Timer', $key);
        }

        return $timer;
    }

    /**
     * 타이머 설정 (upsert)
     * 기존 타이머가 있으면 업데이트, 없으면 생성
     * 삭제된 타이머는 복원 후 업데이트
     */
    public function upsertTimer(string $key, string $targetAt): Timer
    {
        $timer = $this->timerRepository->findByKeyWithTrashed($key);

        if ($timer) {
            if ($timer->trashed()) {
                $this->timerRepository->restore($timer);
            }

            return $this->timerRepository->update($timer, $targetAt);
        }

        return $this->timerRepository->create($key, $targetAt);
    }

    /**
     * 타이머 삭제 (soft delete)
     *
     * @throws NotFoundException
     */
    public function deleteTimer(string $key): Timer
    {
        $timer = $this->timerRepository->findByKey($key);

        if (! $timer) {
            throw NotFoundException::forResource('Timer', $key);
        }

        $this->timerRepository->delete($timer);
        $timer->refresh();

        return $timer;
    }
}
