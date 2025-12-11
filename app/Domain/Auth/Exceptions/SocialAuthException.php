<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\ServiceUnavailableException;

class SocialAuthException
{
    public static function unsupportedProvider(string $provider): BadRequestException
    {
        return (new BadRequestException("지원하지 않는 소셜 로그인 제공자입니다: {$provider}"))
            ->withDetails(['code' => 'UNSUPPORTED_PROVIDER', 'provider' => $provider]);
    }

    public static function authenticationFailed(string $provider): BadRequestException
    {
        return (new BadRequestException('소셜 인증에 실패했습니다.'))
            ->withDetails(['code' => 'SOCIAL_AUTH_FAILED', 'provider' => $provider]);
    }

    public static function accountAlreadyLinked(string $provider): ConflictException
    {
        return (new ConflictException('해당 소셜 계정은 이미 다른 계정에 연동되어 있습니다.'))
            ->withDetails(['code' => 'SOCIAL_ACCOUNT_ALREADY_LINKED', 'provider' => $provider]);
    }

    public static function providerUnavailable(string $provider): ServiceUnavailableException
    {
        return (new ServiceUnavailableException("소셜 로그인 제공자({$provider})에 연결할 수 없습니다."))
            ->withDetails(['code' => 'PROVIDER_UNAVAILABLE', 'provider' => $provider]);
    }

    public static function cannotUnlinkLastProvider(): BadRequestException
    {
        return (new BadRequestException('마지막 로그인 수단은 해제할 수 없습니다.'))
            ->withDetails(['code' => 'CANNOT_UNLINK_LAST_PROVIDER']);
    }

    public static function accountNotLinked(string $provider): BadRequestException
    {
        return (new BadRequestException("해당 소셜 계정이 연동되어 있지 않습니다: {$provider}"))
            ->withDetails(['code' => 'ACCOUNT_NOT_LINKED', 'provider' => $provider]);
    }
}
