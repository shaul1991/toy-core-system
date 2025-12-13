<?php

declare(strict_types=1);

namespace App\Bff\Middleware;

use App\Domain\Auth\Exceptions\TokenException;
use App\Domain\Auth\Services\JwtService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BFF용 JWT 인증 미들웨어
 *
 * Authorization 헤더의 Bearer 토큰을 검증하고,
 * 검증된 사용자 정보를 Request에 추가합니다.
 */
final class JwtAuthenticate
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $optional = ''): Response
    {
        $isOptional = $optional === 'optional';

        $token = $this->extractToken($request);

        if (! $token) {
            if ($isOptional) {
                return $next($request);
            }

            return ApiResponse::error(
                ApiResponseCode::UNAUTHORIZED,
                '인증이 필요합니다.'
            )->toResponse($request);
        }

        try {
            $user = $this->jwtService->validateAccessToken($token);

            // Request에 인증된 사용자 정보 추가
            $request->merge(['authenticated_user' => $user]);
            $request->setUserResolver(fn () => $user);

            // Core Service 호출 시 사용할 헤더 정보 추가
            $request->headers->set('X-User-Id', (string) $user->id);

            return $next($request);
        } catch (TokenException $e) {
            return ApiResponse::error(
                ApiResponseCode::UNAUTHORIZED,
                $e->getMessage()
            )->toResponse($request);
        }
    }

    /**
     * Authorization 헤더에서 Bearer 토큰 추출
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
