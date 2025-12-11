<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Shared\Exceptions\UnauthorizedException;

class TokenException extends UnauthorizedException
{
    public static function expired(): self
    {
        return new self('토큰이 만료되었습니다.');
    }

    public static function invalid(): self
    {
        return new self('유효하지 않은 토큰입니다.');
    }

    public static function revoked(): self
    {
        return new self('토큰이 무효화되었습니다.');
    }

    public static function refreshTokenExpired(): self
    {
        return new self('Refresh Token이 만료되었습니다. 다시 로그인해주세요.');
    }

    public static function tokenReuseDetected(): self
    {
        return (new self('토큰 재사용이 감지되었습니다. 보안을 위해 모든 세션이 종료되었습니다.'))
            ->withDetails(['code' => 'TOKEN_REUSE_DETECTED']);
    }

    public static function allTokensRevoked(): self
    {
        return (new self('모든 토큰이 무효화되었습니다. 다시 로그인해주세요.'))
            ->withDetails(['code' => 'ALL_TOKENS_REVOKED']);
    }
}
