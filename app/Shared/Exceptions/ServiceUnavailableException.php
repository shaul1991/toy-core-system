<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 서비스 이용 불가 예외
 *
 * 외부 서비스 연결 실패, 점검 중 등의 상황에서 사용합니다.
 *
 * @example throw new ServiceUnavailableException('결제 서비스에 연결할 수 없습니다.');
 * @example throw ServiceUnavailableException::forService('PaymentGateway');
 */
class ServiceUnavailableException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::SERVICE_UNAVAILABLE;

    /**
     * 특정 서비스 이용 불가
     */
    public static function forService(string $serviceName): static
    {
        return new static("{$serviceName} 서비스를 현재 이용할 수 없습니다.");
    }

    /**
     * 점검 중
     */
    public static function maintenance(?string $message = null): static
    {
        return new static($message ?? '서비스 점검 중입니다. 잠시 후 다시 시도해주세요.');
    }
}
