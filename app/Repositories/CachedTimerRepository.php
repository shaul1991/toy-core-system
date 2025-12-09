<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Timer;
use Illuminate\Support\Facades\Cache;

/**
 * 캐시 레이어가 적용된 Timer Repository
 * Decorator 패턴으로 EloquentTimerRepository를 래핑
 */
class CachedTimerRepository implements TimerRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 300; // 5분

    private const CACHE_PREFIX = 'timer:';

    public function __construct(
        private EloquentTimerRepository $repository
    ) {}

    /**
     * 키로 타이머 조회 (캐시 적용)
     */
    public function findByKey(string $key): ?Timer
    {
        return Cache::remember(
            $this->getCacheKey($key),
            self::CACHE_TTL_SECONDS,
            fn () => $this->repository->findByKey($key)
        );
    }

    /**
     * 키로 타이머 조회 (삭제된 것 포함, 캐시 미적용)
     * - 삭제된 타이머는 캐시하지 않음
     */
    public function findByKeyWithTrashed(string $key): ?Timer
    {
        return $this->repository->findByKeyWithTrashed($key);
    }

    /**
     * 타이머 생성 (캐시 무효화)
     */
    public function create(string $key, string $targetAt): Timer
    {
        $timer = $this->repository->create($key, $targetAt);
        $this->invalidateCache($key);

        return $timer;
    }

    /**
     * 타이머 업데이트 (캐시 무효화)
     */
    public function update(Timer $timer, string $targetAt): Timer
    {
        $updatedTimer = $this->repository->update($timer, $targetAt);
        $this->invalidateCache($timer->key);

        return $updatedTimer;
    }

    /**
     * 타이머 삭제 (캐시 무효화)
     */
    public function delete(Timer $timer): bool
    {
        $result = $this->repository->delete($timer);
        $this->invalidateCache($timer->key);

        return $result;
    }

    /**
     * 삭제된 타이머 복원 (캐시 무효화)
     */
    public function restore(Timer $timer): bool
    {
        $result = $this->repository->restore($timer);
        $this->invalidateCache($timer->key);

        return $result;
    }

    /**
     * 캐시 키 생성
     */
    private function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }

    /**
     * 캐시 무효화
     */
    private function invalidateCache(string $key): void
    {
        Cache::forget($this->getCacheKey($key));
    }
}
