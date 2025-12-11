<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use Illuminate\Support\Facades\Redis;

final class RedisRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private const PREFIX_REFRESH_TOKEN = 'refresh_token:';

    private const PREFIX_TOKEN_FAMILY = 'token_family:';

    private const PREFIX_BLACKLIST = 'blacklist:access:';

    /**
     * {@inheritDoc}
     */
    public function store(string $tokenId, int $userId, string $familyId, int $ttlSeconds): void
    {
        $key = self::PREFIX_REFRESH_TOKEN.$tokenId;
        $data = json_encode([
            'user_id' => $userId,
            'family' => $familyId,
        ]);

        Redis::setex($key, $ttlSeconds, $data);

        // Token Family에 토큰 ID 추가
        $familyKey = self::PREFIX_TOKEN_FAMILY.$familyId;
        Redis::sadd($familyKey, $tokenId);
        Redis::expire($familyKey, $ttlSeconds);
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $tokenId): ?array
    {
        $key = self::PREFIX_REFRESH_TOKEN.$tokenId;
        $data = Redis::get($key);

        if (! $data) {
            return null;
        }

        $decoded = json_decode($data, true);

        return [
            'user_id' => (int) $decoded['user_id'],
            'family' => $decoded['family'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $tokenId): void
    {
        $key = self::PREFIX_REFRESH_TOKEN.$tokenId;
        Redis::del($key);
    }

    /**
     * {@inheritDoc}
     */
    public function invalidateFamily(string $familyId): void
    {
        $familyKey = self::PREFIX_TOKEN_FAMILY.$familyId;

        // Family에 속한 모든 토큰 ID 조회
        $tokenIds = Redis::smembers($familyKey);

        if (! empty($tokenIds)) {
            // 모든 Refresh Token 삭제
            $keys = array_map(
                fn ($id) => self::PREFIX_REFRESH_TOKEN.$id,
                $tokenIds
            );
            Redis::del($keys);
        }

        // Family 키 삭제
        Redis::del($familyKey);
    }

    /**
     * {@inheritDoc}
     */
    public function blacklistAccessToken(string $tokenId, int $ttlSeconds): void
    {
        $key = self::PREFIX_BLACKLIST.$tokenId;
        Redis::setex($key, $ttlSeconds, '1');
    }

    /**
     * {@inheritDoc}
     */
    public function isAccessTokenBlacklisted(string $tokenId): bool
    {
        $key = self::PREFIX_BLACKLIST.$tokenId;

        return (bool) Redis::exists($key);
    }
}
