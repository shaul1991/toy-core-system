<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\TokenDTO;
use App\Domain\Auth\Exceptions\TokenException;
use App\Domain\Auth\Repositories\RefreshTokenRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

final class JwtService
{
    private const ACCESS_TOKEN_TTL_MINUTES = 60; // 1시간

    private const REFRESH_TOKEN_TTL_SECONDS = 604800; // 7일

    public function __construct(
        private readonly JWTAuth $jwt,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    /**
     * 사용자로부터 토큰 쌍 생성 (로그인 시)
     */
    public function createTokenPair(User $user): TokenDTO
    {
        // Access Token 생성
        $accessToken = $this->createAccessToken($user);

        // 새로운 Token Family 생성
        $familyId = Str::uuid()->toString();

        // Refresh Token 생성 및 저장 (현재 token_version 포함)
        $refreshToken = $this->createRefreshToken($user->id, $familyId, $user->token_version);

        return new TokenDTO(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenType: 'bearer',
            expiresIn: self::ACCESS_TOKEN_TTL_MINUTES * 60,
            user: $user,
        );
    }

    /**
     * Refresh Token으로 새 토큰 쌍 발급 (Token Rotation)
     */
    public function refreshTokenPair(string $refreshToken): TokenDTO
    {
        // Refresh Token ID 추출 (토큰 자체가 ID)
        $tokenData = $this->refreshTokenRepository->find($refreshToken);

        // 토큰이 없는 경우 - 만료되었거나 재사용 시도
        if (! $tokenData) {
            // 재사용 감지 로직: 토큰이 이전에 유효했다면 재사용 시도로 판단
            // 실제로는 토큰이 삭제된 상태이므로 재사용인지 만료인지 구분이 어려움
            // 보안을 위해 사용자에게 재로그인 요청
            throw TokenException::refreshTokenExpired();
        }

        $userId = $tokenData['user_id'];
        $familyId = $tokenData['family'];
        $storedTokenVersion = $tokenData['token_version'];

        // 사용자 조회
        $user = User::find($userId);

        if (! $user) {
            // 사용자가 삭제된 경우 Family 전체 무효화
            $this->refreshTokenRepository->delete($refreshToken);
            $this->refreshTokenRepository->invalidateFamily($familyId);
            throw TokenException::invalid();
        }

        // Token Version 검증 - logoutAll() 호출 후 발급된 토큰인지 확인
        if ($user->token_version > $storedTokenVersion) {
            // 전체 로그아웃 이후 발급된 토큰이 아님 → 무효화
            $this->refreshTokenRepository->delete($refreshToken);
            $this->refreshTokenRepository->invalidateFamily($familyId);
            throw TokenException::allTokensRevoked();
        }

        // 기존 Refresh Token 무효화 (사용됨)
        $this->refreshTokenRepository->delete($refreshToken);

        // 새 Access Token 생성
        $newAccessToken = $this->createAccessToken($user);

        // 새 Refresh Token 생성 (동일 Family, 현재 token_version 저장)
        $newRefreshToken = $this->createRefreshToken($userId, $familyId, $user->token_version);

        return new TokenDTO(
            accessToken: $newAccessToken,
            refreshToken: $newRefreshToken,
            tokenType: 'bearer',
            expiresIn: self::ACCESS_TOKEN_TTL_MINUTES * 60,
            user: $user,
        );
    }

    /**
     * Refresh Token 재사용 감지 시 Family 전체 무효화
     */
    public function handleTokenReuseDetected(string $familyId, int $userId): void
    {
        // 해당 Family의 모든 토큰 무효화
        $this->refreshTokenRepository->invalidateFamily($familyId);

        // 사용자의 토큰 버전 증가 (모든 Access Token도 무효화)
        $user = User::find($userId);
        if ($user) {
            $user->invalidateAllTokens();
        }
    }

    /**
     * 로그아웃 처리
     */
    public function logout(string $accessToken, ?string $refreshToken = null): void
    {
        try {
            // Access Token 파싱
            $this->jwt->setToken($accessToken);
            $payload = $this->jwt->getPayload();

            // Access Token의 남은 시간 계산
            $exp = $payload->get('exp');
            $now = now()->timestamp;
            $ttl = max(0, $exp - $now);

            // Access Token Blacklist에 추가
            $jti = $payload->get('jti');
            if ($jti && $ttl > 0) {
                $this->refreshTokenRepository->blacklistAccessToken($jti, $ttl);
            }
        } catch (JWTException) {
            // 토큰 파싱 실패해도 계속 진행
        }

        // Refresh Token 무효화
        if ($refreshToken) {
            $tokenData = $this->refreshTokenRepository->find($refreshToken);
            if ($tokenData) {
                $this->refreshTokenRepository->delete($refreshToken);
            }
        }
    }

    /**
     * 전체 세션 로그아웃 (모든 디바이스)
     */
    public function logoutAll(User $user): void
    {
        $user->invalidateAllTokens();
    }

    /**
     * Access Token 검증
     */
    public function validateAccessToken(string $token): User
    {
        try {
            $this->jwt->setToken($token);
            $payload = $this->jwt->getPayload();

            // Blacklist 확인
            $jti = $payload->get('jti');
            if ($jti && $this->refreshTokenRepository->isAccessTokenBlacklisted($jti)) {
                throw TokenException::revoked();
            }

            // 사용자 조회
            $user = $this->jwt->authenticate();

            if (! $user) {
                throw TokenException::invalid();
            }

            // Token Version 검증
            $tokenVersion = $payload->get('token_version');
            if ($tokenVersion && $user->token_version > $tokenVersion) {
                throw TokenException::allTokensRevoked();
            }

            return $user;
        } catch (TokenExpiredException) {
            throw TokenException::expired();
        } catch (TokenInvalidException) {
            throw TokenException::invalid();
        }
    }

    /**
     * Access Token에서 사용자 정보 추출 (검증 없이)
     */
    public function parseToken(string $token): ?array
    {
        try {
            $this->jwt->setToken($token);
            $payload = $this->jwt->getPayload();

            return $payload->toArray();
        } catch (JWTException) {
            return null;
        }
    }

    /**
     * Access Token 생성
     */
    private function createAccessToken(User $user): string
    {
        // TTL 설정
        $this->jwt->factory()->setTTL(self::ACCESS_TOKEN_TTL_MINUTES);

        return $this->jwt->fromUser($user);
    }

    /**
     * Refresh Token 생성 및 Redis 저장
     *
     * @param  int  $tokenVersion  사용자의 현재 token_version (전체 로그아웃 검증용)
     */
    private function createRefreshToken(int $userId, string $familyId, int $tokenVersion): string
    {
        // 고유한 Refresh Token ID 생성
        $tokenId = Str::uuid()->toString();

        // Redis에 저장 (token_version 포함)
        $this->refreshTokenRepository->store(
            tokenId: $tokenId,
            userId: $userId,
            familyId: $familyId,
            ttlSeconds: self::REFRESH_TOKEN_TTL_SECONDS,
            tokenVersion: $tokenVersion,
        );

        return $tokenId;
    }
}
