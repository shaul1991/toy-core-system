<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Exceptions;

use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Exceptions\ForbiddenException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ServiceUnavailableException;
use App\Shared\Exceptions\UnauthorizedException;
use App\Shared\Http\ApiResponseCode;
use PHPUnit\Framework\TestCase;

class DomainExceptionTest extends TestCase
{
    public function test_not_found_exception_has_correct_code(): void
    {
        $exception = new NotFoundException('사용자를 찾을 수 없습니다.');

        $this->assertEquals(ApiResponseCode::NOT_FOUND, $exception->getResponseCode());
        $this->assertEquals(404, $exception->getHttpStatus());
        $this->assertEquals('사용자를 찾을 수 없습니다.', $exception->getMessage());
    }

    public function test_not_found_exception_for_resource(): void
    {
        $exception = NotFoundException::forResource('User', 123);

        $this->assertStringContainsString('User', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    public function test_not_found_exception_for_criteria(): void
    {
        $exception = NotFoundException::forCriteria('User', ['email' => 'test@example.com', 'status' => 'active']);

        $this->assertStringContainsString('User', $exception->getMessage());
        $this->assertStringContainsString('email=test@example.com', $exception->getMessage());
        $this->assertStringContainsString('status=active', $exception->getMessage());
    }

    public function test_bad_request_exception_has_correct_code(): void
    {
        $exception = new BadRequestException('잘못된 요청입니다.');

        $this->assertEquals(ApiResponseCode::BAD_REQUEST, $exception->getResponseCode());
        $this->assertEquals(400, $exception->getHttpStatus());
    }

    public function test_unauthorized_exception_has_correct_code(): void
    {
        $exception = new UnauthorizedException('로그인이 필요합니다.');

        $this->assertEquals(ApiResponseCode::UNAUTHORIZED, $exception->getResponseCode());
        $this->assertEquals(401, $exception->getHttpStatus());
    }

    public function test_forbidden_exception_has_correct_code(): void
    {
        $exception = new ForbiddenException('접근 권한이 없습니다.');

        $this->assertEquals(ApiResponseCode::FORBIDDEN, $exception->getResponseCode());
        $this->assertEquals(403, $exception->getHttpStatus());
    }

    public function test_forbidden_exception_for_resource(): void
    {
        $exception = ForbiddenException::forResource('Post', 123);

        $this->assertStringContainsString('Post', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    public function test_forbidden_exception_for_action(): void
    {
        $exception = ForbiddenException::forAction('delete');

        $this->assertStringContainsString('delete', $exception->getMessage());
    }

    public function test_conflict_exception_has_correct_code(): void
    {
        $exception = new ConflictException('이미 존재하는 리소스입니다.');

        $this->assertEquals(ApiResponseCode::CONFLICT, $exception->getResponseCode());
        $this->assertEquals(409, $exception->getHttpStatus());
    }

    public function test_conflict_exception_duplicate_field(): void
    {
        $exception = ConflictException::duplicateField('email', 'test@example.com');

        $this->assertStringContainsString('email', $exception->getMessage());
        $this->assertStringContainsString('test@example.com', $exception->getMessage());
    }

    public function test_conflict_exception_resource_exists(): void
    {
        $exception = ConflictException::resourceExists('User', 'test@example.com');

        $this->assertStringContainsString('User', $exception->getMessage());
        $this->assertStringContainsString('test@example.com', $exception->getMessage());
    }

    public function test_domain_validation_exception_has_correct_code(): void
    {
        $exception = new DomainValidationException('입력값이 올바르지 않습니다.');

        $this->assertEquals(ApiResponseCode::VALIDATION_ERROR, $exception->getResponseCode());
        $this->assertEquals(400, $exception->getHttpStatus());
    }

    public function test_domain_validation_exception_with_errors(): void
    {
        $errors = [
            'email' => '이메일 형식이 올바르지 않습니다.',
            'name' => '이름은 필수입니다.',
        ];
        $exception = DomainValidationException::withErrors($errors);

        $this->assertEquals($errors, $exception->getDetails());
    }

    public function test_domain_validation_exception_for_field(): void
    {
        $exception = DomainValidationException::forField('email', '이메일 형식이 올바르지 않습니다.');

        $this->assertEquals(['email' => '이메일 형식이 올바르지 않습니다.'], $exception->getDetails());
    }

    public function test_business_exception_has_correct_code(): void
    {
        $exception = new BusinessException('재고가 부족합니다.');

        $this->assertEquals(ApiResponseCode::BAD_REQUEST, $exception->getResponseCode());
        $this->assertEquals(400, $exception->getHttpStatus());
    }

    public function test_business_exception_with_custom_code(): void
    {
        $exception = BusinessException::withCode(ApiResponseCode::CONFLICT, '이미 처리된 주문입니다.');

        $this->assertEquals(ApiResponseCode::CONFLICT, $exception->getResponseCode());
        $this->assertEquals(409, $exception->getHttpStatus());
    }

    public function test_service_unavailable_exception_has_correct_code(): void
    {
        $exception = new ServiceUnavailableException('서비스를 이용할 수 없습니다.');

        $this->assertEquals(ApiResponseCode::SERVICE_UNAVAILABLE, $exception->getResponseCode());
        $this->assertEquals(503, $exception->getHttpStatus());
    }

    public function test_service_unavailable_exception_for_service(): void
    {
        $exception = ServiceUnavailableException::forService('PaymentGateway');

        $this->assertStringContainsString('PaymentGateway', $exception->getMessage());
    }

    public function test_service_unavailable_exception_maintenance(): void
    {
        $exception = ServiceUnavailableException::maintenance();

        $this->assertStringContainsString('점검', $exception->getMessage());
    }

    public function test_exception_with_details(): void
    {
        $exception = new NotFoundException('사용자를 찾을 수 없습니다.');
        $exception->withDetails(['searched_id' => 123, 'searched_at' => '2024-01-01']);

        $this->assertEquals([
            'searched_id' => 123,
            'searched_at' => '2024-01-01',
        ], $exception->getDetails());
    }

    public function test_exception_to_array(): void
    {
        $exception = new NotFoundException('사용자를 찾을 수 없습니다.');

        $array = $exception->toArray();

        $this->assertEquals('NOT_FOUND', $array['code']);
        $this->assertEquals('사용자를 찾을 수 없습니다.', $array['message']);
        $this->assertArrayNotHasKey('details', $array);
    }

    public function test_exception_to_array_with_details(): void
    {
        $exception = DomainValidationException::withErrors(['email' => '이메일 형식이 올바르지 않습니다.']);

        $array = $exception->toArray();

        $this->assertEquals('VALIDATION_ERROR', $array['code']);
        $this->assertArrayHasKey('details', $array);
        $this->assertEquals(['email' => '이메일 형식이 올바르지 않습니다.'], $array['details']);
    }

    public function test_exception_uses_default_message_when_null(): void
    {
        $exception = new NotFoundException;

        $this->assertEquals(ApiResponseCode::NOT_FOUND->defaultMessage(), $exception->getMessage());
    }
}
