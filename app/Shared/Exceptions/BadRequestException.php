<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 잘못된 요청일 때 발생하는 예외
 *
 * @example throw new BadRequestException('요청 파라미터가 올바르지 않습니다.');
 * @example throw BadRequestException::invalidValue('visibility', 'invalid');
 */
class BadRequestException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::BAD_REQUEST;

    /**
     * 유효하지 않은 값으로 예외 생성
     */
    public static function invalidValue(string $field, mixed $value): static
    {
        return (new static("유효하지 않은 {$field} 값입니다: {$value}"))
            ->withDetails(['field' => $field, 'value' => $value]);
    }
}
