<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Http;

use App\Shared\Http\ApiResponseCode;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Cursor;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiResponsableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestRoutes();
    }

    private function registerTestRoutes(): void
    {
        Route::prefix('test-api')->group(function () {
            Route::get('/success', [TestApiController::class, 'success']);
            Route::get('/success-with-message', [TestApiController::class, 'successWithMessage']);
            Route::get('/success-empty', [TestApiController::class, 'successEmpty']);
            Route::post('/created', [TestApiController::class, 'created']);
            Route::post('/created-with-message', [TestApiController::class, 'createdWithMessage']);
            Route::get('/paginated-offset', [TestApiController::class, 'paginatedOffset']);
            Route::get('/paginated-cursor', [TestApiController::class, 'paginatedCursor']);
            Route::get('/not-found', [TestApiController::class, 'notFound']);
            Route::get('/not-found-custom', [TestApiController::class, 'notFoundCustom']);
            Route::get('/validation-error', [TestApiController::class, 'validationError']);
            Route::get('/unauthorized', [TestApiController::class, 'unauthorized']);
            Route::get('/forbidden', [TestApiController::class, 'forbidden']);
            Route::get('/custom-error', [TestApiController::class, 'customError']);
        });
    }

    public function test_success_response_returns_200_with_data(): void
    {
        $response = $this->getJson('/test-api/success');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'Test User',
                ],
            ])
            ->assertJsonMissing(['error', 'pagination']);
    }

    public function test_success_response_with_message(): void
    {
        $response = $this->getJson('/test-api/success-with-message');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => 1],
                'message' => '조회 성공',
            ]);
    }

    public function test_success_response_with_empty_data(): void
    {
        $response = $this->getJson('/test-api/success-empty');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);
    }

    public function test_created_response_returns_201(): void
    {
        $response = $this->postJson('/test-api/created');

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'New User',
                ],
                'message' => ApiResponseCode::CREATED->defaultMessage(),
            ]);
    }

    public function test_created_response_with_custom_message(): void
    {
        $response = $this->postJson('/test-api/created-with-message');

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '사용자가 생성되었습니다.',
            ]);
    }

    public function test_paginated_response_with_offset(): void
    {
        $response = $this->getJson('/test-api/paginated-offset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
                'pagination' => [
                    'type' => 'offset',
                    'page' => 1,
                    'per_page' => 15,
                    'total' => 100,
                    'last_page' => 7,
                    'has_more_pages' => true,
                ],
            ]);
    }

    public function test_paginated_response_with_cursor(): void
    {
        $response = $this->getJson('/test-api/paginated-cursor');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
                'pagination' => [
                    'type' => 'cursor',
                    'per_page' => 15,
                    'next_cursor' => 'next_cursor_encoded',
                    'prev_cursor' => null,
                    'has_more_pages' => true,
                ],
            ]);
    }

    public function test_not_found_response(): void
    {
        $response = $this->getJson('/test-api/not-found');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => ApiResponseCode::NOT_FOUND->defaultMessage(),
                ],
            ])
            ->assertJsonMissing(['data']);
    }

    public function test_not_found_response_with_custom_message(): void
    {
        $response = $this->getJson('/test-api/not-found-custom');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '사용자를 찾을 수 없습니다.',
                ],
            ]);
    }

    public function test_validation_error_response(): void
    {
        $response = $this->getJson('/test-api/validation-error');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => ApiResponseCode::VALIDATION_ERROR->defaultMessage(),
                    'details' => [
                        'email' => '이메일 형식이 올바르지 않습니다.',
                        'name' => '이름은 필수입니다.',
                    ],
                ],
            ]);
    }

    public function test_unauthorized_response(): void
    {
        $response = $this->getJson('/test-api/unauthorized');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => ApiResponseCode::UNAUTHORIZED->defaultMessage(),
                ],
            ]);
    }

    public function test_forbidden_response(): void
    {
        $response = $this->getJson('/test-api/forbidden');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => ApiResponseCode::FORBIDDEN->defaultMessage(),
                ],
            ]);
    }

    public function test_custom_error_response(): void
    {
        $response = $this->getJson('/test-api/custom-error');

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => '이미 존재하는 리소스입니다.',
                    'details' => [
                        'field' => 'email',
                        'value' => 'test@example.com',
                    ],
                ],
            ]);
    }

    public function test_response_has_json_content_type(): void
    {
        $response = $this->getJson('/test-api/success');

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}

/**
 * Test controller that uses the ApiResponsable trait.
 */
class TestApiController extends Controller
{
    use ApiResponsable;

    public function success(): JsonResponse
    {
        return $this->successResponse(['id' => 1, 'name' => 'Test User']);
    }

    public function successWithMessage(): JsonResponse
    {
        return $this->successResponse(['id' => 1], '조회 성공');
    }

    public function successEmpty(): JsonResponse
    {
        return $this->successResponse();
    }

    public function created(): JsonResponse
    {
        return $this->createdResponse(['id' => 1, 'name' => 'New User']);
    }

    public function createdWithMessage(): JsonResponse
    {
        return $this->createdResponse(['id' => 1], '사용자가 생성되었습니다.');
    }

    public function paginatedOffset(): JsonResponse
    {
        $paginator = $this->createMockOffsetPaginator();

        return $this->paginatedResponse($paginator);
    }

    public function paginatedCursor(): JsonResponse
    {
        $paginator = $this->createMockCursorPaginator();

        return $this->paginatedResponse($paginator);
    }

    public function notFound(): JsonResponse
    {
        return $this->notFoundResponse();
    }

    public function notFoundCustom(): JsonResponse
    {
        return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
    }

    public function validationError(): JsonResponse
    {
        return $this->validationErrorResponse([
            'email' => '이메일 형식이 올바르지 않습니다.',
            'name' => '이름은 필수입니다.',
        ]);
    }

    public function unauthorized(): JsonResponse
    {
        return $this->unauthorizedResponse();
    }

    public function forbidden(): JsonResponse
    {
        return $this->forbiddenResponse();
    }

    public function customError(): JsonResponse
    {
        return $this->errorResponse(
            ApiResponseCode::CONFLICT,
            '이미 존재하는 리소스입니다.',
            ['field' => 'email', 'value' => 'test@example.com']
        );
    }

    private function createMockOffsetPaginator(): LengthAwarePaginator
    {
        return new class implements LengthAwarePaginator
        {
            public function url($page): string
            {
                return '';
            }

            public function appends($key, $value = null): static
            {
                return $this;
            }

            public function fragment($fragment = null): static
            {
                return $this;
            }

            public function nextPageUrl(): ?string
            {
                return null;
            }

            public function previousPageUrl(): ?string
            {
                return null;
            }

            public function items(): array
            {
                return [['id' => 1], ['id' => 2]];
            }

            public function firstItem(): ?int
            {
                return 1;
            }

            public function lastItem(): ?int
            {
                return 2;
            }

            public function perPage(): int
            {
                return 15;
            }

            public function currentPage(): int
            {
                return 1;
            }

            public function hasPages(): bool
            {
                return true;
            }

            public function hasMorePages(): bool
            {
                return true;
            }

            public function path(): ?string
            {
                return '/';
            }

            public function isEmpty(): bool
            {
                return false;
            }

            public function isNotEmpty(): bool
            {
                return true;
            }

            public function render($view = null, $data = []): string
            {
                return '';
            }

            public function total(): int
            {
                return 100;
            }

            public function lastPage(): int
            {
                return 7;
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->items());
            }

            public function count(): int
            {
                return count($this->items());
            }

            public function toArray(): array
            {
                return $this->items();
            }

            public function jsonSerialize(): array
            {
                return $this->toArray();
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }

            public function getUrlRange($start, $end): array
            {
                return [];
            }

            public function onEachSide($count): static
            {
                return $this;
            }

            public function linkCollection(): \Illuminate\Support\Collection
            {
                return collect([]);
            }

            public function links($view = null, $data = []): string
            {
                return '';
            }

            public function setPath($path): static
            {
                return $this;
            }

            public function through(callable $callback): static
            {
                return $this;
            }

            public function withPath($path): static
            {
                return $this;
            }

            public function withQueryString(): static
            {
                return $this;
            }
        };
    }

    private function createMockCursorPaginator(): CursorPaginator
    {
        return new class implements CursorPaginator
        {
            public function url($cursor): string
            {
                return '';
            }

            public function appends($key, $value = null): static
            {
                return $this;
            }

            public function fragment($fragment = null): static
            {
                return $this;
            }

            public function nextPageUrl(): ?string
            {
                return null;
            }

            public function previousPageUrl(): ?string
            {
                return null;
            }

            public function items(): array
            {
                return [['id' => 1], ['id' => 2]];
            }

            public function perPage(): int
            {
                return 15;
            }

            public function cursor(): ?Cursor
            {
                return null;
            }

            public function nextCursor(): ?Cursor
            {
                return new class extends Cursor
                {
                    public function __construct()
                    {
                        parent::__construct(['id' => 2]);
                    }

                    public function encode(): string
                    {
                        return 'next_cursor_encoded';
                    }
                };
            }

            public function previousCursor(): ?Cursor
            {
                return null;
            }

            public function getCursorName(): string
            {
                return 'cursor';
            }

            public function hasPages(): bool
            {
                return true;
            }

            public function hasMorePages(): bool
            {
                return true;
            }

            public function path(): ?string
            {
                return '/';
            }

            public function isEmpty(): bool
            {
                return false;
            }

            public function isNotEmpty(): bool
            {
                return true;
            }

            public function render($view = null, $data = []): string
            {
                return '';
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->items());
            }

            public function count(): int
            {
                return count($this->items());
            }

            public function toArray(): array
            {
                return $this->items();
            }

            public function jsonSerialize(): array
            {
                return $this->toArray();
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }

            public function onEachSide($count): static
            {
                return $this;
            }

            public function setPath($path): static
            {
                return $this;
            }

            public function through(callable $callback): static
            {
                return $this;
            }

            public function withPath($path): static
            {
                return $this;
            }

            public function withQueryString(): static
            {
                return $this;
            }
        };
    }
}
