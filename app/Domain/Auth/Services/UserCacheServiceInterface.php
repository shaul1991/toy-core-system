<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Models\User;

/**
 * 사용자 캐시 서비스 인터페이스
 */
interface UserCacheServiceInterface
{
    /**
     * 캐시에서 사용자 조회 (없으면 DB에서 조회 후 캐시)
     */
    public function find(int $userId): ?User;

    /**
     * 캐시에서 사용자 조회 (token_version 포함 검증)
     */
    public function findWithVersionCheck(int $userId, int $expectedVersion): ?User;

    /**
     * 사용자 캐시 무효화
     */
    public function invalidate(int $userId): void;

    /**
     * 사용자 캐시 갱신 (명시적 업데이트)
     */
    public function refresh(User $user): void;
}
