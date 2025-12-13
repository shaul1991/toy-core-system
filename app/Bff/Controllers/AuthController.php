<?php

declare(strict_types=1);

namespace App\Bff\Controllers;

use App\Bff\Services\CoreAuthService;
use App\Domain\Auth\Exceptions\TokenException;
use App\Http\Controllers\Controller;
use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\ServiceUnavailableException;
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

    /**
     * 쿠키 설정 캐시
     *
     * @var array{domain: ?string, secure: bool}|null
     */
    private ?array $cookieSettings = null;

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
     * @param  Request  $request  쿠키 설정(domain/secure) 결정에 사용
     * @param  string  $provider  소셜 제공자 (github, naver, kakao)
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        try {
            $tokenDto = $this->coreAuthService->handleSocialCallback($provider);

            // 프론트엔드 콜백 URL로 리다이렉트
            $callbackUrl = $frontendUrl . '/auth/callback';
            $accessTokenMinutes = (int) ceil($tokenDto->expiresIn / 60);

            // 토큰을 HttpOnly, Secure 쿠키로 설정
            return redirect()->away($callbackUrl)
                ->withCookie($this->makeAuthCookie($request, 'access_token', $tokenDto->accessToken, $accessTokenMinutes, true))
                ->withCookie($this->makeAuthCookie($request, 'refresh_token', $tokenDto->refreshToken, 60 * 24 * 7, true))
                ->withCookie($this->makeAuthCookie($request, 'token_type', $tokenDto->tokenType, $accessTokenMinutes, false));
        } catch (BadRequestException|ConflictException|ServiceUnavailableException $e) {
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

        try {
            $tokenDto = $this->coreAuthService->refreshToken($refreshToken);
            $accessTokenMinutes = (int) ceil($tokenDto->expiresIn / 60);

            // 새 토큰을 쿠키로 설정하여 응답
            return $this->successResponse([
                'user' => [
                    'id' => $tokenDto->user->id,
                    'name' => $tokenDto->user->name,
                    'email' => $tokenDto->user->email,
                    'avatar' => $tokenDto->user->avatar,
                ],
            ])
                ->withCookie($this->makeAuthCookie($request, 'access_token', $tokenDto->accessToken, $accessTokenMinutes, true))
                ->withCookie($this->makeAuthCookie($request, 'refresh_token', $tokenDto->refreshToken, 60 * 24 * 7, true))
                ->withCookie($this->makeAuthCookie($request, 'token_type', $tokenDto->tokenType, $accessTokenMinutes, false));
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

        // 토큰 쿠키 삭제 (domain/path 일치 필요)
        return $this->successResponse(null, '로그아웃되었습니다.')
            ->withCookie($this->forgetAuthCookie($request, 'access_token'))
            ->withCookie($this->forgetAuthCookie($request, 'refresh_token'))
            ->withCookie($this->forgetAuthCookie($request, 'token_type'));
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

        // 토큰 쿠키 삭제 (domain/path 일치 필요)
        return $this->successResponse(null, '모든 세션에서 로그아웃되었습니다.')
            ->withCookie($this->forgetAuthCookie($request, 'access_token'))
            ->withCookie($this->forgetAuthCookie($request, 'refresh_token'))
            ->withCookie($this->forgetAuthCookie($request, 'token_type'));
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

    /**
     * 쿠키 설정 동적 결정
     *
     * 프로덕션 환경에서는 config/session.php에서 domain을 명시적으로 설정하는 것을 권장합니다.
     * 자동 추출은 일부 2단계 TLD에서 올바르게 동작하지 않을 수 있습니다.
     *
     * 권장 설정 (.env):
     *   SESSION_DOMAIN=.example.com
     *   SESSION_SECURE_COOKIE=true
     *
     * @return array{domain: ?string, secure: bool}
     */
    private function getCookieSettings(Request $request): array
    {
        // 캐시된 설정 반환
        if ($this->cookieSettings !== null) {
            return $this->cookieSettings;
        }

        // 1. secure 결정: 명시적 설정 > 요청 스킴
        $secure = config('session.secure');
        if ($secure === null) {
            // 요청이 HTTPS인지 확인 (프록시 뒤에서도 동작하도록 isSecure() 사용)
            $secure = $request->isSecure();
        }

        // 2. domain 결정: 명시적 설정 우선 (권장)
        $domain = config('session.domain');

        if ($domain === null) {
            // 명시적 설정이 없으면 프론트엔드 URL에서 추출 시도 (폴백)
            // 주의: 이 방식은 일부 국가별 TLD에서 올바르게 동작하지 않을 수 있음
            $domain = $this->extractDomainFromFrontendUrl();
        }

        $this->cookieSettings = [
            'domain' => $domain,
            'secure' => (bool) $secure,
        ];

        return $this->cookieSettings;
    }

    /**
     * 프론트엔드 URL에서 쿠키 도메인 추출 (폴백용)
     *
     * 주의: 프로덕션에서는 SESSION_DOMAIN 환경 변수 사용을 권장합니다.
     * 2단계 TLD 목록이 완전하지 않아 일부 도메인에서 오작동할 수 있습니다.
     */
    private function extractDomainFromFrontendUrl(): ?string
    {
        $frontendUrl = config('app.frontend_url');

        if (! $frontendUrl) {
            return null;
        }

        $parsedUrl = parse_url($frontendUrl);
        $host = $parsedUrl['host'] ?? null;

        // localhost나 IP 주소는 domain을 null로 유지
        if (! $host || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }

        // 서브도메인 공유를 위해 베이스 도메인 추출 후 앞에 점(.) 붙임
        // 예: app.example.com → .example.com
        // 주의: 이 목록은 완전하지 않음. 프로덕션에서는 명시적 설정 권장
        $twoLevelTlds = [
            // 아시아
            'co.kr', 'co.jp', 'co.th', 'co.id', 'co.in', 'co.nz',
            'com.cn', 'com.tw', 'com.hk', 'com.sg', 'com.my', 'com.ph', 'com.vn',
            // 유럽
            'co.uk', 'org.uk', 'me.uk', 'co.il',
            // 아메리카
            'com.br', 'com.mx', 'com.ar', 'com.co',
            // 오세아니아/아프리카
            'com.au', 'co.za', 'co.ke',
        ];

        $lastTwo = implode('.', array_slice($parts, -2));

        if (in_array($lastTwo, $twoLevelTlds, true) && count($parts) >= 3) {
            return '.' . implode('.', array_slice($parts, -3));
        }

        return '.' . implode('.', array_slice($parts, -2));
    }

    /**
     * 인증 쿠키 생성
     *
     * @param  string  $name  쿠키 이름
     * @param  string  $value  쿠키 값
     * @param  int  $minutes  만료 시간(분)
     * @param  bool  $httpOnly  HttpOnly 속성
     */
    private function makeAuthCookie(Request $request, string $name, string $value, int $minutes, bool $httpOnly = true): \Symfony\Component\HttpFoundation\Cookie
    {
        $settings = $this->getCookieSettings($request);

        return cookie(
            name: $name,
            value: $value,
            minutes: $minutes,
            path: '/',
            domain: $settings['domain'],
            secure: $settings['secure'],
            httpOnly: $httpOnly,
            raw: false,
            sameSite: 'Lax'
        );
    }

    /**
     * 인증 쿠키 삭제
     *
     * 쿠키 삭제 시 설정했던 domain/path와 일치해야 브라우저에서 삭제됨
     *
     * @param  string  $name  쿠키 이름
     */
    private function forgetAuthCookie(Request $request, string $name): \Symfony\Component\HttpFoundation\Cookie
    {
        $settings = $this->getCookieSettings($request);

        return cookie(
            name: $name,
            value: '',
            minutes: -2628000, // 과거 시간으로 설정하여 삭제
            path: '/',
            domain: $settings['domain'],
            secure: $settings['secure'],
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );
    }
}
