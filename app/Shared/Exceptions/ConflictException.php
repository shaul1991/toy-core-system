<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 리소스 충돌이 발생했을 때 발생하는 예외
 *
 * @example throw new ConflictException('이미 존재하는 이메일입니다.');
 * @example throw ConflictException::duplicateField('email', 'test@example.com');
 */
class ConflictException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::CONFLICT;

    /**
     * 중복 필드 예외 생성
     */
    public static function duplicateField(string $field, mixed $value): static
    {
        return new static("이미 존재하는 {$field}입니다: {$value}");
    }

    /**
     * 리소스가 이미 존재할 때
     */
    public static function resourceExists(string $resourceType, mixed $id): static
    {
        return new static("{$resourceType}(이)가 이미 존재합니다: {$id}");
    }
}
