<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * 사용자 정보 캐싱 서비스
 *
 * JWT 검증, 토큰 갱신 등에서 빈번하게 발생하는 사용자 조회를 최적화합니다.
 */
final class UserCacheService implements UserCacheServiceInterface
{
    private const CACHE_PREFIX = 'user:';

    private const CACHE_TTL_SECONDS = 300; // 5분

    /**
     * 캐시에서 사용자 조회 (없으면 DB에서 조회 후 캐시)
     */
    public function find(int $userId): ?User
    {
        $cacheKey = self::CACHE_PREFIX.$userId;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => User::find($userId)
        );
    }

    /**
     * 캐시에서 사용자 조회 (token_version 포함 검증)
     *
     * token_version이 불일치하면 캐시를 무효화하고 DB에서 다시 조회
     */
    public function findWithVersionCheck(int $userId, int $expectedVersion): ?User
    {
        $user = $this->find($userId);

        if ($user && $user->token_version !== $expectedVersion) {
            // 캐시된 버전이 오래됨 - 갱신
            $this->invalidate($userId);
            $user = $this->find($userId);
        }

        return $user;
    }

    /**
     * 사용자 캐시 무효화
     */
    public function invalidate(int $userId): void
    {
        $cacheKey = self::CACHE_PREFIX.$userId;
        Cache::forget($cacheKey);
    }

    /**
     * 사용자 캐시 갱신 (명시적 업데이트)
     */
    public function refresh(User $user): void
    {
        $cacheKey = self::CACHE_PREFIX.$user->id;
        Cache::put($cacheKey, $user, self::CACHE_TTL_SECONDS);
    }
}
