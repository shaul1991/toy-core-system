<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 잘못된 요청일 때 발생하는 예외
 *
 * @example throw new BadRequestException('요청 파라미터가 올바르지 않습니다.');
 */
class BadRequestException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::BAD_REQUEST;
}
