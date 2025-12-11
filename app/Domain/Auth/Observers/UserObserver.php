<?php

declare(strict_types=1);

namespace App\Domain\Auth\Observers;

use App\Domain\Auth\Services\UserCacheServiceInterface;
use App\Models\User;

/**
 * User 모델 옵저버
 *
 * 사용자 정보 변경 시 캐시 무효화를 자동으로 처리합니다.
 */
final class UserObserver
{
    public function __construct(
        private readonly UserCacheServiceInterface $userCacheService,
    ) {}

    /**
     * 사용자 정보 업데이트 시 캐시 무효화
     */
    public function updated(User $user): void
    {
        $this->userCacheService->invalidate($user->id);
    }

    /**
     * 사용자 삭제 시 캐시 무효화
     */
    public function deleted(User $user): void
    {
        $this->userCacheService->invalidate($user->id);
    }
}
