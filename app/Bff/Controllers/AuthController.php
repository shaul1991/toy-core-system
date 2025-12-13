<?php

declare(strict_types=1);

namespace App\Bff\Controllers;

use App\Bff\Services\CoreAuthService;
use App\Domain\Auth\Exceptions\SocialAuthException;
use App\Domain\Auth\Exceptions\TokenException;
use App\Http\Controllers\Controller;
use App\Shared\Exceptions\BadRequestException;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * BFF Auth 컨트롤러
 *
 * 프론트엔드와 Core Service 사이의 인증 흐름을 처리합니다.
 * - JWT 토큰 관리 (발급, 검증, 갱신)
 * - 소셜 로그인 흐름 처리
 * - 세션 관리 (로그아웃)
 */
final class AuthController extends Controller
{
    use ApiResponsable;

    public function __construct(
        private readonly CoreAuthService $coreAuthService,
    ) {}

    /**
     * 소셜 로그인 리다이렉트
     *
     * OAuth 제공자의 인증 페이지로 리다이렉트합니다.
     *
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function redirect(string $provider): RedirectResponse
    {
        $redirectUrl = $this->coreAuthService->getRedirectUrl($provider);

        return redirect()->away($redirectUrl);
    }

    /**
     * 소셜 로그인 콜백
     *
     * OAuth 콜백을 처리하고 JWT 토큰을 발급합니다.
     * 토큰은 HttpOnly 쿠키로 설정하여 보안을 강화합니다.
     *
     * @param  Request  $request  OAuth 에러 처리를 위해 유지
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $isSecure = config('app.env') === 'production';

        try {
            $tokenDto = $this->coreAuthService->handleSocialCallback($provider);

            // 프론트엔드 콜백 URL로 리다이렉트
            $callbackUrl = $frontendUrl . '/auth/callback';

            // 토큰을 HttpOnly, Secure 쿠키로 설정
            return redirect()->away($callbackUrl)
                ->withCookie(cookie(
                    name: 'access_token',
                    value: $tokenDto->accessToken,
                    minutes: (int) ceil($tokenDto->expiresIn / 60),
                    path: '/',
                    domain: null,
                    secure: $isSecure,
                    httpOnly: true,
                    raw: false,
                    sameSite: 'Lax'
                ))
                ->withCookie(cookie(
                    name: 'refresh_token',
                    value: $tokenDto->refreshToken,
                    minutes: 60 * 24 * 7, // 7일
                    path: '/',
                    domain: null,
                    secure: $isSecure,
                    httpOnly: true,
                    raw: false,
                    sameSite: 'Lax'
                ))
                ->withCookie(cookie(
                    name: 'token_type',
                    value: $tokenDto->tokenType,
                    minutes: (int) ceil($tokenDto->expiresIn / 60),
                    path: '/',
                    domain: null,
                    secure: $isSecure,
                    httpOnly: false, // 프론트엔드에서 읽을 수 있도록
                    raw: false,
                    sameSite: 'Lax'
                ));
        } catch (SocialAuthException $e) {
            // 에러 시 프론트엔드 에러 페이지로 리다이렉트
            $errorUrl = $frontendUrl . '/auth/error';

            return redirect()->away($errorUrl . '?' . http_build_query([
                'error' => $e->getCode(),
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * 토큰 갱신
     *
     * Refresh Token으로 새 Access Token을 발급합니다.
     * 쿠키 또는 요청 본문에서 refresh_token을 읽습니다.
     */
    public function refresh(Request $request): JsonResponse
    {
        // 쿠키 또는 요청 본문에서 refresh_token 추출
        $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');

        if (! $refreshToken) {
            return $this->badRequestResponse('refresh_token이 필요합니다.');
        }

        $isSecure = config('app.env') === 'production';

        try {
            $tokenDto = $this->coreAuthService->refreshToken($refreshToken);

            // 새 토큰을 쿠키로 설정하여 응답
            return $this->successResponse([
                'user' => [
                    'id' => $tokenDto->user->id,
                    'name' => $tokenDto->user->name,
                    'email' => $tokenDto->user->email,
                    'avatar' => $tokenDto->user->avatar,
                ],
            ])->withCookie(cookie(
                name: 'access_token',
                value: $tokenDto->accessToken,
                minutes: (int) ceil($tokenDto->expiresIn / 60),
                path: '/',
                domain: null,
                secure: $isSecure,
                httpOnly: true,
                raw: false,
                sameSite: 'Lax'
            ))->withCookie(cookie(
                name: 'refresh_token',
                value: $tokenDto->refreshToken,
                minutes: 60 * 24 * 7, // 7일
                path: '/',
                domain: null,
                secure: $isSecure,
                httpOnly: true,
                raw: false,
                sameSite: 'Lax'
            ))->withCookie(cookie(
                name: 'token_type',
                value: $tokenDto->tokenType,
                minutes: (int) ceil($tokenDto->expiresIn / 60),
                path: '/',
                domain: null,
                secure: $isSecure,
                httpOnly: false,
                raw: false,
                sameSite: 'Lax'
            ));
        } catch (TokenException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }
    }

    /**
     * 토큰 검증
     *
     * Access Token의 유효성을 검증합니다.
     * 쿠키 또는 Authorization 헤더에서 토큰을 읽습니다.
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return $this->unauthorizedResponse('인증 토큰이 필요합니다.');
        }

        try {
            $user = $this->coreAuthService->validateToken($token);

            return $this->successResponse([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ]);
        } catch (TokenException $e) {
            return $this->successResponse([
                'valid' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 현재 사용자 정보 조회
     *
     * 인증된 사용자의 정보를 반환합니다.
     * JWT 미들웨어를 통해 인증된 사용자만 접근 가능합니다.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('인증이 필요합니다.');
        }

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at?->toIsoString(),
            'created_at' => $user->created_at->toIsoString(),
            'updated_at' => $user->updated_at->toIsoString(),
        ]);
    }

    /**
     * 로그아웃
     *
     * 현재 세션의 토큰을 무효화하고 쿠키를 삭제합니다.
     */
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $this->extractToken($request);
        $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');

        if ($accessToken) {
            $this->coreAuthService->logout($accessToken, $refreshToken);
        }

        // 토큰 쿠키 삭제
        return $this->successResponse(null, '로그아웃되었습니다.')
            ->withCookie(cookie()->forget('access_token'))
            ->withCookie(cookie()->forget('refresh_token'))
            ->withCookie(cookie()->forget('token_type'));
    }

    /**
     * 전체 세션 로그아웃
     *
     * 모든 디바이스에서 로그아웃하고 쿠키를 삭제합니다.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('인증이 필요합니다.');
        }

        $this->coreAuthService->logoutAll($user);

        // 토큰 쿠키 삭제
        return $this->successResponse(null, '모든 세션에서 로그아웃되었습니다.')
            ->withCookie(cookie()->forget('access_token'))
            ->withCookie(cookie()->forget('refresh_token'))
            ->withCookie(cookie()->forget('token_type'));
    }

    /**
     * 연동된 소셜 계정 목록 조회
     */
    public function socialAccounts(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('인증이 필요합니다.');
        }

        $accounts = $this->coreAuthService->getLinkedSocialAccounts($user);

        return $this->successResponse($accounts);
    }

    /**
     * 소셜 계정 연동
     *
     * 기존 계정에 소셜 계정을 연동합니다.
     *
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function link(Request $request, string $provider): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // 인증되지 않은 사용자는 소셜 계정 연동 불가
        if (! $user) {
            return $this->forbiddenResponse('소셜 계정 연동을 위해서는 로그인이 필요합니다.');
        }

        // 연동 모드로 세션에 저장
        session(['social_link_mode' => true, 'social_link_user_id' => $user->id]);

        $redirectUrl = $this->coreAuthService->getRedirectUrl($provider);

        return redirect()->away($redirectUrl);
    }

    /**
     * 소셜 계정 연동 해제
     *
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function unlink(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('인증이 필요합니다.');
        }

        try {
            $this->coreAuthService->unlinkSocialAccount($user, $provider);

            return $this->successResponse(null, '소셜 계정 연동이 해제되었습니다.');
        } catch (BadRequestException|SocialAuthException $e) {
            return $this->badRequestResponse($e->getMessage());
        }
    }

    /**
     * 토큰 추출 (Authorization 헤더 우선, 없으면 쿠키에서)
     */
    private function extractToken(Request $request): ?string
    {
        // 1. Authorization 헤더에서 Bearer 토큰 추출
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // 2. 쿠키에서 access_token 추출
        return $request->cookie('access_token');
    }
}
