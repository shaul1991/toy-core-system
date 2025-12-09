<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_returns_json_response_with_correct_structure(): void
    {
        $data = ['id' => 1, 'name' => 'Test User'];

        $response = ApiResponse::success($data)->toResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($data, $content['data']);
        $this->assertArrayNotHasKey('message', $content);
        $this->assertArrayNotHasKey('error', $content);
    }

    public function test_success_with_message(): void
    {
        $data = ['id' => 1];
        $message = '성공적으로 조회되었습니다.';

        $response = ApiResponse::success($data, $message)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals($message, $content['message']);
    }

    public function test_success_with_null_data(): void
    {
        $response = ApiResponse::success()->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertNull($content['data']);
    }

    public function test_success_with_empty_array_data(): void
    {
        $response = ApiResponse::success([])->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals([], $content['data']);
    }

    public function test_created_returns_201_status(): void
    {
        $data = ['id' => 1, 'name' => 'New User'];

        $response = ApiResponse::created($data)->toResponse();

        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($data, $content['data']);
        $this->assertEquals(ApiResponseCode::CREATED->defaultMessage(), $content['message']);
    }

    public function test_created_with_custom_message(): void
    {
        $message = '사용자가 생성되었습니다.';

        $response = ApiResponse::created(null, $message)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals($message, $content['message']);
    }

    #[DataProvider('errorCodesProvider')]
    public function test_error_returns_correct_http_status(ApiResponseCode $code, int $expectedStatus): void
    {
        $response = ApiResponse::error($code)->toResponse();

        $this->assertEquals($expectedStatus, $response->getStatusCode());
    }

    public static function errorCodesProvider(): array
    {
        return [
            'BAD_REQUEST' => [ApiResponseCode::BAD_REQUEST, 400],
            'UNAUTHORIZED' => [ApiResponseCode::UNAUTHORIZED, 401],
            'FORBIDDEN' => [ApiResponseCode::FORBIDDEN, 403],
            'NOT_FOUND' => [ApiResponseCode::NOT_FOUND, 404],
            'VALIDATION_ERROR' => [ApiResponseCode::VALIDATION_ERROR, 400],
            'CONFLICT' => [ApiResponseCode::CONFLICT, 409],
            'TOO_MANY_REQUESTS' => [ApiResponseCode::TOO_MANY_REQUESTS, 429],
            'INTERNAL_ERROR' => [ApiResponseCode::INTERNAL_ERROR, 500],
            'SERVICE_UNAVAILABLE' => [ApiResponseCode::SERVICE_UNAVAILABLE, 503],
        ];
    }

    public function test_error_returns_correct_structure(): void
    {
        $response = ApiResponse::error(ApiResponseCode::NOT_FOUND, '사용자를 찾을 수 없습니다.')->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('NOT_FOUND', $content['error']['code']);
        $this->assertEquals('사용자를 찾을 수 없습니다.', $content['error']['message']);
        $this->assertArrayNotHasKey('details', $content['error']);
        $this->assertArrayNotHasKey('data', $content);
    }

    public function test_error_uses_default_message_when_null(): void
    {
        $response = ApiResponse::error(ApiResponseCode::NOT_FOUND)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(ApiResponseCode::NOT_FOUND->defaultMessage(), $content['error']['message']);
    }

    public function test_error_with_details(): void
    {
        $details = [
            'email' => '이메일 형식이 올바르지 않습니다.',
            'name' => '이름은 필수입니다.',
        ];

        $response = ApiResponse::error(
            ApiResponseCode::VALIDATION_ERROR,
            '입력값 검증에 실패했습니다.',
            $details
        )->toResponse();

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('details', $content['error']);
        $this->assertEquals($details, $content['error']['details']);
    }

    public function test_paginated_with_offset_paginator(): void
    {
        $items = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ];

        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn($items);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(100);
        $paginator->method('lastPage')->willReturn(7);
        $paginator->method('hasMorePages')->willReturn(true);

        $response = ApiResponse::paginated($paginator)->toResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals($items, $content['data']);
        $this->assertArrayHasKey('pagination', $content);
        $this->assertEquals('offset', $content['pagination']['type']);
        $this->assertEquals(1, $content['pagination']['page']);
        $this->assertEquals(15, $content['pagination']['per_page']);
        $this->assertEquals(100, $content['pagination']['total']);
        $this->assertEquals(7, $content['pagination']['last_page']);
        $this->assertTrue($content['pagination']['has_more_pages']);
    }

    public function test_paginated_with_cursor_paginator(): void
    {
        $items = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ];

        $nextCursor = $this->createMock(Cursor::class);
        $nextCursor->method('encode')->willReturn('eyJpZCI6MjB9');

        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('items')->willReturn($items);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('nextCursor')->willReturn($nextCursor);
        $paginator->method('previousCursor')->willReturn(null);
        $paginator->method('hasMorePages')->willReturn(true);

        $response = ApiResponse::paginated($paginator)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals($items, $content['data']);
        $this->assertArrayHasKey('pagination', $content);
        $this->assertEquals('cursor', $content['pagination']['type']);
        $this->assertEquals(15, $content['pagination']['per_page']);
        $this->assertEquals('eyJpZCI6MjB9', $content['pagination']['next_cursor']);
        $this->assertNull($content['pagination']['prev_cursor']);
        $this->assertTrue($content['pagination']['has_more_pages']);
    }

    public function test_paginated_with_message(): void
    {
        $message = '사용자 목록입니다.';

        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([]);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(0);
        $paginator->method('lastPage')->willReturn(1);
        $paginator->method('hasMorePages')->willReturn(false);

        $response = ApiResponse::paginated($paginator, $message)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals($message, $content['message']);
    }

    public function test_paginated_last_page_has_no_more_pages(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([]);
        $paginator->method('currentPage')->willReturn(7);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(100);
        $paginator->method('lastPage')->willReturn(7);
        $paginator->method('hasMorePages')->willReturn(false);

        $response = ApiResponse::paginated($paginator)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['pagination']['has_more_pages']);
    }

    public function test_with_message_modifies_message(): void
    {
        $originalMessage = '원래 메시지';
        $newMessage = '새 메시지';

        $response = ApiResponse::success(null, $originalMessage)
            ->withMessage($newMessage)
            ->toResponse();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals($newMessage, $content['message']);
    }

    public function test_with_code_modifies_response_code(): void
    {
        $response = ApiResponse::success(['data' => 'test'])
            ->withCode(ApiResponseCode::CREATED)
            ->toResponse();

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_success_response_does_not_include_error(): void
    {
        $response = ApiResponse::success(['id' => 1])->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('error', $content);
    }

    public function test_error_response_does_not_include_data(): void
    {
        $response = ApiResponse::error(ApiResponseCode::NOT_FOUND)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('data', $content);
    }

    public function test_success_response_does_not_include_pagination_when_not_paginated(): void
    {
        $response = ApiResponse::success(['id' => 1])->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('pagination', $content);
    }

    public function test_complex_nested_data_structure(): void
    {
        $data = [
            'user' => [
                'id' => 1,
                'profile' => [
                    'name' => 'Test User',
                    'settings' => [
                        'notifications' => true,
                        'theme' => 'dark',
                    ],
                ],
                'roles' => ['admin', 'user'],
            ],
        ];

        $response = ApiResponse::success($data)->toResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals($data, $content['data']);
    }

    public function test_json_response_has_correct_content_type(): void
    {
        $response = ApiResponse::success()->toResponse();

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}
