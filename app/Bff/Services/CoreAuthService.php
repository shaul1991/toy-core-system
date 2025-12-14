<?php

declare(strict_types=1);

namespace App\Bff\Services;

use App\Domain\Auth\DTOs\TokenDTO;
use App\Domain\Auth\Exceptions\SocialAuthException;
use App\Domain\Auth\Exceptions\TokenException;
use App\Domain\Auth\Services\JwtService;
use App\Domain\Auth\Services\SocialAuthService;
use App\Models\User;

/**
 * BFF용 Core Auth Service 클라이언트
 *
 * Core Service의 인증 관련 기능을 호출하고,
 * BFF에 맞는 형태로 응답을 변환합니다.
 */
final class CoreAuthService
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * 소셜 로그인 리다이렉트 URL 조회
     */
    public function getRedirectUrl(string $provider): string
    {
        return $this->socialAuthService->getRedirectUrl($provider);
    }

    /**
     * 소셜 계정 연동용 리다이렉트 URL 조회 (state 파라미터 포함)
     */
    public function getRedirectUrlWithState(string $provider, string $state): string
    {
        return $this->socialAuthService->getRedirectUrlWithState($provider, $state);
    }

    /**
     * 소셜 로그인 콜백 처리 (로그인/회원가입)
     */
    public function handleSocialCallback(string $provider): TokenDTO
    {
        return $this->socialAuthService->handleCallback($provider);
    }

    /**
     * 토큰 갱신
     */
    public function refreshToken(string $refreshToken): TokenDTO
    {
        return $this->jwtService->refreshTokenPair($refreshToken);
    }

    /**
     * Access Token 검증
     */
    public function validateToken(string $accessToken): User
    {
        return $this->jwtService->validateAccessToken($accessToken);
    }

    /**
     * 로그아웃
     */
    public function logout(string $accessToken, ?string $refreshToken = null): void
    {
        $this->jwtService->logout($accessToken, $refreshToken);
    }

    /**
     * 전체 세션 로그아웃 (모든 디바이스)
     */
    public function logoutAll(User $user): void
    {
        $this->jwtService->logoutAll($user);
    }

    /**
     * 사용자 정보 조회
     */
    public function getUser(int $userId): ?User
    {
        return User::find($userId);
    }

    /**
     * 소셜 계정 연동
     */
    public function linkSocialAccount(User $user, string $provider): void
    {
        $this->socialAuthService->linkAccount($user, $provider);
    }

    /**
     * 소셜 계정 연동 해제
     */
    public function unlinkSocialAccount(User $user, string $provider): void
    {
        $this->socialAuthService->unlinkAccount($user, $provider);
    }

    /**
     * 연동된 소셜 계정 목록 조회
     */
    public function getLinkedSocialAccounts(User $user): array
    {
        return $this->socialAuthService->getLinkedAccounts($user);
    }
}
