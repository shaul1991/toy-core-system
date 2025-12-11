<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

interface RefreshTokenRepositoryInterface
{
    /**
     * Refresh Token 저장
     *
     * @param  int  $tokenVersion  사용자의 현재 token_version (전체 로그아웃 검증용)
     */
    public function store(string $tokenId, int $userId, string $familyId, int $ttlSeconds, int $tokenVersion): void;

    /**
     * Refresh Token 조회
     *
     * @return array{user_id: int, family: string, token_version: int}|null
     */
    public function find(string $tokenId): ?array;

    /**
     * Refresh Token 삭제 (사용됨)
     */
    public function delete(string $tokenId): void;

    /**
     * Token Family 전체 무효화
     */
    public function invalidateFamily(string $familyId): void;

    /**
     * Access Token Blacklist에 추가
     */
    public function blacklistAccessToken(string $tokenId, int $ttlSeconds): void;

    /**
     * Access Token이 Blacklist에 있는지 확인
     */
    public function isAccessTokenBlacklisted(string $tokenId): bool;
}
