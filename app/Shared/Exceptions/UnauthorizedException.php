<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 인증이 필요할 때 발생하는 예외
 *
 * @example throw new UnauthorizedException('로그인이 필요합니다.');
 * @example throw new UnauthorizedException('토큰이 만료되었습니다.');
 */
class UnauthorizedException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::UNAUTHORIZED;
}
