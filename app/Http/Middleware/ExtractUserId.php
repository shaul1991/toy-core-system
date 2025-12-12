<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\Exceptions\BadRequestException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Domain Service용 사용자 ID 추출 미들웨어
 *
 * BFF/Aggregator에서 JWT 검증 후 전달하는 X-User-Id 헤더를 추출합니다.
 * Domain Service는 JWT 검증을 수행하지 않고 단순히 user_id만 받습니다.
 */
class ExtractUserId
{
    public const HEADER_NAME = 'X-User-Id';

    public const REQUEST_KEY = 'authenticated_user_id';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $required = 'required'): Response
    {
        $userId = $request->header(self::HEADER_NAME);

        if ($required === 'required' && empty($userId)) {
            throw new BadRequestException(
                sprintf('%s 헤더가 필요합니다.', self::HEADER_NAME)
            );
        }

        if (! empty($userId)) {
            if (! is_numeric($userId) || (int) $userId <= 0) {
                throw new BadRequestException(
                    sprintf('%s 헤더 값이 유효하지 않습니다.', self::HEADER_NAME)
                );
            }

            $request->attributes->set(self::REQUEST_KEY, (int) $userId);
        }

        return $next($request);
    }
}
