<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 도메인 레벨 유효성 검증 실패 예외
 *
 * Laravel의 ValidationException과 구분되는 도메인 규칙 검증용입니다.
 *
 * @example throw DomainValidationException::withErrors(['email' => '이메일 형식이 올바르지 않습니다.']);
 * @example throw new DomainValidationException('나이는 0보다 커야 합니다.');
 */
class DomainValidationException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::VALIDATION_ERROR;

    /**
     * 필드별 에러 메시지로 예외 생성
     *
     * @param  array<string, string|array<string>>  $errors
     */
    public static function withErrors(array $errors, ?string $message = null): static
    {
        $exception = new static($message);
        $exception->details = $errors;

        return $exception;
    }

    /**
     * 단일 필드 에러로 예외 생성
     */
    public static function forField(string $field, string $message): static
    {
        return static::withErrors([$field => $message], $message);
    }
}
