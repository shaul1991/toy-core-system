<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 일반적인 비즈니스 로직 예외
 *
 * 특정 카테고리에 속하지 않는 비즈니스 규칙 위반 시 사용합니다.
 *
 * @example throw new BusinessException('재고가 부족합니다.');
 * @example throw new BusinessException('이미 처리된 주문입니다.');
 */
class BusinessException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::BAD_REQUEST;

    /**
     * 커스텀 응답 코드로 예외 생성
     */
    public static function withCode(ApiResponseCode $code, ?string $message = null): static
    {
        $exception = new static($message);
        $exception->responseCode = $code;

        return $exception;
    }
}
