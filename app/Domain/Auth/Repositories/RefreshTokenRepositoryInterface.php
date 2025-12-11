<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

interface RefreshTokenRepositoryInterface
{
    /**
     * Refresh Token 저장
     */
    public function store(string $tokenId, int $userId, string $familyId, int $ttlSeconds): void;

    /**
     * Refresh Token 조회
     *
     * @return array{user_id: int, family: string}|null
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
