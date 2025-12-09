<?php

declare(strict_types=1);

namespace App\Shared\Http;

enum ApiResponseCode: string
{
    // Success
    case SUCCESS = 'SUCCESS';
    case CREATED = 'CREATED';
    case UPDATED = 'UPDATED';
    case DELETED = 'DELETED';

    // Client Errors (4xx)
    case BAD_REQUEST = 'BAD_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case CONFLICT = 'CONFLICT';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';

    // Server Errors (5xx)
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    public function httpStatus(): int
    {
        return match ($this) {
            self::SUCCESS, self::UPDATED, self::DELETED => 200,
            self::CREATED => 201,
            self::BAD_REQUEST, self::VALIDATION_ERROR => 400,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::CONFLICT => 409,
            self::TOO_MANY_REQUESTS => 429,
            self::INTERNAL_ERROR => 500,
            self::SERVICE_UNAVAILABLE => 503,
        };
    }

    public function defaultMessage(): string
    {
        return match ($this) {
            self::SUCCESS => '요청이 성공적으로 처리되었습니다.',
            self::CREATED => '리소스가 생성되었습니다.',
            self::UPDATED => '리소스가 수정되었습니다.',
            self::DELETED => '리소스가 삭제되었습니다.',
            self::BAD_REQUEST => '잘못된 요청입니다.',
            self::UNAUTHORIZED => '인증이 필요합니다.',
            self::FORBIDDEN => '접근 권한이 없습니다.',
            self::NOT_FOUND => '리소스를 찾을 수 없습니다.',
            self::VALIDATION_ERROR => '입력값 검증에 실패했습니다.',
            self::CONFLICT => '리소스 충돌이 발생했습니다.',
            self::TOO_MANY_REQUESTS => '요청이 너무 많습니다.',
            self::INTERNAL_ERROR => '서버 오류가 발생했습니다.',
            self::SERVICE_UNAVAILABLE => '서비스를 사용할 수 없습니다.',
        };
    }
}
