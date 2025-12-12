<?php

declare(strict_types=1);

namespace App\Shared\Http\Traits;

use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponsable
{
    protected function successResponse(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::success($data, $message)->toResponse();
    }

    protected function createdResponse(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::created($data, $message)->toResponse();
    }

    protected function paginatedResponse(
        LengthAwarePaginator|CursorPaginator $paginator,
        ?callable $transformer = null,
        ?string $message = null
    ): JsonResponse {
        return ApiResponse::paginated($paginator, $transformer, $message)->toResponse();
    }

    protected function paginatedArrayResponse(
        array $data,
        int $total,
        int $page,
        int $perPage,
        ?string $message = null
    ): JsonResponse {
        return ApiResponse::paginatedArray($data, $total, $page, $perPage, $message)->toResponse();
    }

    protected function deletedResponse(?string $message = null): JsonResponse
    {
        return ApiResponse::deleted($message)->toResponse();
    }

    protected function errorResponse(
        ApiResponseCode $code,
        ?string $message = null,
        ?array $details = null
    ): JsonResponse {
        return ApiResponse::error($code, $message, $details)->toResponse();
    }

    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(ApiResponseCode::NOT_FOUND, $message);
    }

    protected function validationErrorResponse(array $errors, ?string $message = null): JsonResponse
    {
        return $this->errorResponse(ApiResponseCode::VALIDATION_ERROR, $message, $errors);
    }

    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(ApiResponseCode::UNAUTHORIZED, $message);
    }

    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(ApiResponseCode::FORBIDDEN, $message);
    }
}
