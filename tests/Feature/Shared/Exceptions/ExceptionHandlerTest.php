<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Exceptions;

use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Exceptions\ForbiddenException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ServiceUnavailableException;
use App\Shared\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 테스트용 API 라우트 등록
        Route::prefix('api')->group(function () {
            Route::get('/test/not-found', fn () => throw new NotFoundException('테스트 리소스를 찾을 수 없습니다.'));
            Route::get('/test/bad-request', fn () => throw new BadRequestException('잘못된 요청입니다.'));
            Route::get('/test/unauthorized', fn () => throw new UnauthorizedException('인증이 필요합니다.'));
            Route::get('/test/forbidden', fn () => throw new ForbiddenException('접근 권한이 없습니다.'));
            Route::get('/test/conflict', fn () => throw new ConflictException('리소스 충돌이 발생했습니다.'));
            Route::get('/test/validation', fn () => throw DomainValidationException::withErrors([
                'email' => '이메일 형식이 올바르지 않습니다.',
                'name' => '이름은 필수입니다.',
            ]));
            Route::get('/test/service-unavailable', fn () => throw new ServiceUnavailableException('서비스를 이용할 수 없습니다.'));
        });
    }

    public function test_not_found_exception_returns_404_json_response(): void
    {
        $response = $this->getJson('/api/test/not-found');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '테스트 리소스를 찾을 수 없습니다.',
                ],
            ]);
    }

    public function test_bad_request_exception_returns_400_json_response(): void
    {
        $response = $this->getJson('/api/test/bad-request');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'BAD_REQUEST',
                    'message' => '잘못된 요청입니다.',
                ],
            ]);
    }

    public function test_unauthorized_exception_returns_401_json_response(): void
    {
        $response = $this->getJson('/api/test/unauthorized');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => '인증이 필요합니다.',
                ],
            ]);
    }

    public function test_forbidden_exception_returns_403_json_response(): void
    {
        $response = $this->getJson('/api/test/forbidden');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => '접근 권한이 없습니다.',
                ],
            ]);
    }

    public function test_conflict_exception_returns_409_json_response(): void
    {
        $response = $this->getJson('/api/test/conflict');

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => '리소스 충돌이 발생했습니다.',
                ],
            ]);
    }

    public function test_domain_validation_exception_returns_400_json_response_with_details(): void
    {
        $response = $this->getJson('/api/test/validation');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => [
                        'email' => '이메일 형식이 올바르지 않습니다.',
                        'name' => '이름은 필수입니다.',
                    ],
                ],
            ]);
    }

    public function test_service_unavailable_exception_returns_503_json_response(): void
    {
        $response = $this->getJson('/api/test/service-unavailable');

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => '서비스를 이용할 수 없습니다.',
                ],
            ]);
    }

    public function test_response_structure_matches_api_response_format(): void
    {
        $response = $this->getJson('/api/test/not-found');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);
    }
}
