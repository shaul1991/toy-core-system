<?php

declare(strict_types=1);

namespace App\Shared\Http\Traits;

use App\Http\Middleware\ExtractUserId;
use Illuminate\Http\Request;

/**
 * 인증된 사용자 ID 접근 트레이트
 *
 * BFF/Aggregator에서 JWT 검증 후 전달한 X-User-Id 헤더 값을 쉽게 접근합니다.
 */
trait HasAuthenticatedUserId
{
    /**
     * 요청에서 인증된 사용자 ID 가져오기
     */
    protected function getAuthenticatedUserId(Request $request): ?int
    {
        return $request->attributes->get(ExtractUserId::REQUEST_KEY);
    }

    /**
     * 인증된 사용자 ID가 필수인 경우 (없으면 예외)
     */
    protected function requireAuthenticatedUserId(Request $request): int
    {
        $userId = $this->getAuthenticatedUserId($request);

        if ($userId === null) {
            throw new \RuntimeException('User ID is required but not found in request');
        }

        return $userId;
    }
}
