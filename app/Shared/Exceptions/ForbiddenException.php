<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

/**
 * 접근 권한이 없을 때 발생하는 예외
 *
 * @example throw new ForbiddenException('이 리소스에 접근할 권한이 없습니다.');
 * @example throw ForbiddenException::forResource('Post', 123);
 */
class ForbiddenException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::FORBIDDEN;

    /**
     * 특정 리소스에 대한 접근 권한 없음
     */
    public static function forResource(string $resourceType, mixed $id): static
    {
        return new static("{$resourceType}(ID: {$id})에 접근할 권한이 없습니다.");
    }

    /**
     * 특정 액션에 대한 권한 없음
     */
    public static function forAction(string $action): static
    {
        return new static("'{$action}' 작업을 수행할 권한이 없습니다.");
    }
}
