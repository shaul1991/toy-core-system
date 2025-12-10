<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 리소스를 찾을 수 없을 때 발생하는 예외
 *
 * @example throw new NotFoundException('사용자를 찾을 수 없습니다.');
 * @example throw NotFoundException::forResource('User', 123);
 */
class NotFoundException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::NOT_FOUND;

    /**
     * 리소스 타입과 ID로 예외 생성
     */
    public static function forResource(string $resourceType, mixed $id): static
    {
        return new static("{$resourceType}(을)를 찾을 수 없습니다: {$id}");
    }

    /**
     * 조건으로 예외 생성
     */
    public static function forCriteria(string $resourceType, array $criteria): static
    {
        $criteriaString = collect($criteria)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode(', ');

        return new static("{$resourceType}(을)를 찾을 수 없습니다: [{$criteriaString}]");
    }

    /**
     * 메시지로 예외 생성
     */
    public static function withMessage(string $message): static
    {
        return new static($message);
    }
}
