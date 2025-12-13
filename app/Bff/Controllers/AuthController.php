<?php

declare(strict_types=1);

namespace App\Bff\Controllers;

use App\Bff\Services\CoreAuthService;
use App\Domain\Auth\Exceptions\SocialAuthException;
use App\Domain\Auth\Exceptions\TokenException;
use App\Http\Controllers\Controller;
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
     * 프론트엔드로 토큰 정보와 함께 리다이렉트합니다.
     *
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function callback(Request $request, string $provider): RedirectResponse|JsonResponse
    {
        try {
            $tokenDto = $this->coreAuthService->handleSocialCallback($provider);

            // 프론트엔드 콜백 URL로 리다이렉트
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $callbackUrl = $frontendUrl . '/auth/callback';

            // 토큰 정보를 쿼리 파라미터로 전달 (또는 쿠키로 설정 가능)
            return redirect()->away($callbackUrl . '?' . http_build_query([
                'access_token' => $tokenDto->accessToken,
                'refresh_token' => $tokenDto->refreshToken,
                'token_type' => $tokenDto->tokenType,
                'expires_in' => $tokenDto->expiresIn,
            ]));
        } catch (SocialAuthException $e) {
            // 에러 시 프론트엔드 에러 페이지로 리다이렉트
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
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
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (! $refreshToken) {
            return $this->badRequestResponse('refresh_token이 필요합니다.');
        }

        try {
            $tokenDto = $this->coreAuthService->refreshToken($refreshToken);

            return $this->successResponse([
                'access_token' => $tokenDto->accessToken,
                'refresh_token' => $tokenDto->refreshToken,
                'token_type' => $tokenDto->tokenType,
                'expires_in' => $tokenDto->expiresIn,
                'user' => [
                    'id' => $tokenDto->user->id,
                    'name' => $tokenDto->user->name,
                    'email' => $tokenDto->user->email,
                    'avatar' => $tokenDto->user->avatar,
                ],
            ]);
        } catch (TokenException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }
    }

    /**
     * 토큰 검증
     *
     * Access Token의 유효성을 검증합니다.
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);

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
     * 현재 세션의 토큰을 무효화합니다.
     */
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $this->extractBearerToken($request);
        $refreshToken = $request->input('refresh_token');

        if ($accessToken) {
            $this->coreAuthService->logout($accessToken, $refreshToken);
        }

        return $this->successResponse(null, '로그아웃되었습니다.');
    }

    /**
     * 전체 세션 로그아웃
     *
     * 모든 디바이스에서 로그아웃합니다.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse('인증이 필요합니다.');
        }

        $this->coreAuthService->logoutAll($user);

        return $this->successResponse(null, '모든 세션에서 로그아웃되었습니다.');
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
    public function link(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();

        // 연동 모드로 세션에 저장
        session(['social_link_mode' => true, 'social_link_user_id' => $user?->id]);

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
        } catch (SocialAuthException $e) {
            return $this->badRequestResponse($e->getMessage());
        }
    }

    /**
     * Authorization 헤더에서 Bearer 토큰 추출
     */
    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
