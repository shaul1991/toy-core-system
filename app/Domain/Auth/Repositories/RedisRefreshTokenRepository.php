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
     *
     * Pipeline을 사용하여 3개의 Redis 명령을 단일 round-trip으로 실행
     */
    public function store(string $tokenId, int $userId, string $familyId, int $ttlSeconds, int $tokenVersion): void
    {
        $key = self::PREFIX_REFRESH_TOKEN.$tokenId;
        $familyKey = self::PREFIX_TOKEN_FAMILY.$familyId;
        $data = json_encode([
            'user_id' => $userId,
            'family' => $familyId,
            'token_version' => $tokenVersion,
        ]);

        // Pipeline으로 3개 명령을 단일 round-trip으로 최적화
        Redis::pipeline(function ($pipe) use ($key, $familyKey, $tokenId, $data, $ttlSeconds) {
            $pipe->setex($key, $ttlSeconds, $data);
            $pipe->sadd($familyKey, $tokenId);
            $pipe->expire($familyKey, $ttlSeconds);
        });
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
            'token_version' => (int) ($decoded['token_version'] ?? 1),
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
     *
     * Lua Script를 사용하여 원자적으로 Family의 모든 토큰을 삭제
     */
    public function invalidateFamily(string $familyId): void
    {
        $familyKey = self::PREFIX_TOKEN_FAMILY.$familyId;

        // Lua Script로 원자적 실행 (SMEMBERS + DEL을 서버 사이드에서 처리)
        $luaScript = <<<'LUA'
            local familyKey = KEYS[1]
            local prefix = ARGV[1]

            local tokenIds = redis.call('SMEMBERS', familyKey)

            if #tokenIds > 0 then
                local keys = {}
                for i, tokenId in ipairs(tokenIds) do
                    keys[i] = prefix .. tokenId
                end
                redis.call('DEL', unpack(keys))
            end

            redis.call('DEL', familyKey)

            return #tokenIds
        LUA;

        Redis::eval($luaScript, 1, $familyKey, self::PREFIX_REFRESH_TOKEN);
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
