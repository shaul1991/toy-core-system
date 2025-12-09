<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\ApiResponseCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiResponseCodeTest extends TestCase
{
    #[DataProvider('httpStatusProvider')]
    public function test_http_status_mapping(ApiResponseCode $code, int $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $code->httpStatus());
    }

    public static function httpStatusProvider(): array
    {
        return [
            'SUCCESS returns 200' => [ApiResponseCode::SUCCESS, 200],
            'CREATED returns 201' => [ApiResponseCode::CREATED, 201],
            'UPDATED returns 200' => [ApiResponseCode::UPDATED, 200],
            'DELETED returns 200' => [ApiResponseCode::DELETED, 200],
            'BAD_REQUEST returns 400' => [ApiResponseCode::BAD_REQUEST, 400],
            'UNAUTHORIZED returns 401' => [ApiResponseCode::UNAUTHORIZED, 401],
            'FORBIDDEN returns 403' => [ApiResponseCode::FORBIDDEN, 403],
            'NOT_FOUND returns 404' => [ApiResponseCode::NOT_FOUND, 404],
            'VALIDATION_ERROR returns 400' => [ApiResponseCode::VALIDATION_ERROR, 400],
            'CONFLICT returns 409' => [ApiResponseCode::CONFLICT, 409],
            'TOO_MANY_REQUESTS returns 429' => [ApiResponseCode::TOO_MANY_REQUESTS, 429],
            'INTERNAL_ERROR returns 500' => [ApiResponseCode::INTERNAL_ERROR, 500],
            'SERVICE_UNAVAILABLE returns 503' => [ApiResponseCode::SERVICE_UNAVAILABLE, 503],
        ];
    }

    #[DataProvider('defaultMessageProvider')]
    public function test_default_message(ApiResponseCode $code, string $expectedMessage): void
    {
        $this->assertEquals($expectedMessage, $code->defaultMessage());
    }

    public static function defaultMessageProvider(): array
    {
        return [
            'SUCCESS' => [ApiResponseCode::SUCCESS, '요청이 성공적으로 처리되었습니다.'],
            'CREATED' => [ApiResponseCode::CREATED, '리소스가 생성되었습니다.'],
            'UPDATED' => [ApiResponseCode::UPDATED, '리소스가 수정되었습니다.'],
            'DELETED' => [ApiResponseCode::DELETED, '리소스가 삭제되었습니다.'],
            'BAD_REQUEST' => [ApiResponseCode::BAD_REQUEST, '잘못된 요청입니다.'],
            'UNAUTHORIZED' => [ApiResponseCode::UNAUTHORIZED, '인증이 필요합니다.'],
            'FORBIDDEN' => [ApiResponseCode::FORBIDDEN, '접근 권한이 없습니다.'],
            'NOT_FOUND' => [ApiResponseCode::NOT_FOUND, '리소스를 찾을 수 없습니다.'],
            'VALIDATION_ERROR' => [ApiResponseCode::VALIDATION_ERROR, '입력값 검증에 실패했습니다.'],
            'CONFLICT' => [ApiResponseCode::CONFLICT, '리소스 충돌이 발생했습니다.'],
            'TOO_MANY_REQUESTS' => [ApiResponseCode::TOO_MANY_REQUESTS, '요청이 너무 많습니다.'],
            'INTERNAL_ERROR' => [ApiResponseCode::INTERNAL_ERROR, '서버 오류가 발생했습니다.'],
            'SERVICE_UNAVAILABLE' => [ApiResponseCode::SERVICE_UNAVAILABLE, '서비스를 사용할 수 없습니다.'],
        ];
    }

    public function test_code_values_are_uppercase_strings(): void
    {
        foreach (ApiResponseCode::cases() as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z_]+$/', $code->value);
        }
    }

    public function test_all_success_codes_have_2xx_status(): void
    {
        $successCodes = [
            ApiResponseCode::SUCCESS,
            ApiResponseCode::CREATED,
            ApiResponseCode::UPDATED,
            ApiResponseCode::DELETED,
        ];

        foreach ($successCodes as $code) {
            $status = $code->httpStatus();
            $this->assertGreaterThanOrEqual(200, $status);
            $this->assertLessThan(300, $status);
        }
    }

    public function test_all_client_error_codes_have_4xx_status(): void
    {
        $clientErrorCodes = [
            ApiResponseCode::BAD_REQUEST,
            ApiResponseCode::UNAUTHORIZED,
            ApiResponseCode::FORBIDDEN,
            ApiResponseCode::NOT_FOUND,
            ApiResponseCode::VALIDATION_ERROR,
            ApiResponseCode::CONFLICT,
            ApiResponseCode::TOO_MANY_REQUESTS,
        ];

        foreach ($clientErrorCodes as $code) {
            $status = $code->httpStatus();
            $this->assertGreaterThanOrEqual(400, $status);
            $this->assertLessThan(500, $status);
        }
    }

    public function test_all_server_error_codes_have_5xx_status(): void
    {
        $serverErrorCodes = [
            ApiResponseCode::INTERNAL_ERROR,
            ApiResponseCode::SERVICE_UNAVAILABLE,
        ];

        foreach ($serverErrorCodes as $code) {
            $status = $code->httpStatus();
            $this->assertGreaterThanOrEqual(500, $status);
            $this->assertLessThan(600, $status);
        }
    }

    public function test_default_message_is_not_empty(): void
    {
        foreach (ApiResponseCode::cases() as $code) {
            $this->assertNotEmpty($code->defaultMessage());
        }
    }
}
