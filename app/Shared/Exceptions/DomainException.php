<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;
use Exception;
use Throwable;

/**
 * 도메인 예외 베이스 클래스
 *
 * 모든 도메인 예외는 이 클래스를 상속받아 구현합니다.
 * ApiResponseCode와 연동되어 자동으로 API 응답으로 변환됩니다.
 */
abstract class DomainException extends Exception
{
    protected ApiResponseCode $responseCode = ApiResponseCode::INTERNAL_ERROR;

    protected ?array $details = null;

    public function __construct(
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message ?? $this->responseCode->defaultMessage(),
            $this->responseCode->httpStatus(),
            $previous
        );
    }

    /**
     * API 응답 코드 반환
     */
    public function getResponseCode(): ApiResponseCode
    {
        return $this->responseCode;
    }

    /**
     * HTTP 상태 코드 반환
     */
    public function getHttpStatus(): int
    {
        return $this->responseCode->httpStatus();
    }

    /**
     * 에러 상세 정보 반환
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * 에러 상세 정보 설정
     */
    public function withDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * API 에러 응답용 배열 변환
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->responseCode->value,
            'message' => $this->getMessage(),
        ];

        if ($this->details !== null) {
            $error['details'] = $this->details;
        }

        return $error;
    }
}
