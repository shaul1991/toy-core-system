<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Http\Pagination\CursorPagination;
use App\Shared\Http\Pagination\OffsetPagination;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    private bool $success = true;

    private mixed $data = null;

    private ?string $message = null;

    private ?ApiResponseCode $code = null;

    private ?array $error = null;

    private ?array $pagination = null;

    private function __construct() {}

    public static function success(mixed $data = null, ?string $message = null): self
    {
        $response = new self;
        $response->success = true;
        $response->data = $data;
        $response->message = $message;
        $response->code = ApiResponseCode::SUCCESS;

        return $response;
    }

    public static function created(mixed $data = null, ?string $message = null): self
    {
        $response = new self;
        $response->success = true;
        $response->data = $data;
        $response->message = $message ?? ApiResponseCode::CREATED->defaultMessage();
        $response->code = ApiResponseCode::CREATED;

        return $response;
    }

    public static function error(
        ApiResponseCode $code,
        ?string $message = null,
        ?array $details = null
    ): self {
        $response = new self;
        $response->success = false;
        $response->code = $code;
        $response->error = [
            'code' => $code->value,
            'message' => $message ?? $code->defaultMessage(),
        ];

        if ($details !== null) {
            $response->error['details'] = $details;
        }

        return $response;
    }

    public static function paginated(
        LengthAwarePaginator|CursorPaginator $paginator,
        ?callable $transformer = null,
        ?string $message = null
    ): self {
        $response = new self;
        $response->success = true;

        $items = $paginator->items();
        $response->data = $transformer !== null
            ? array_map($transformer, $items)
            : $items;

        $response->message = $message;
        $response->code = ApiResponseCode::SUCCESS;

        if ($paginator instanceof LengthAwarePaginator) {
            $response->pagination = OffsetPagination::fromLengthAwarePaginator($paginator)->toArray();
        } else {
            $response->pagination = CursorPagination::fromCursorPaginator($paginator)->toArray();
        }

        return $response;
    }

    /**
     * Create a paginated response from array data
     */
    public static function paginatedArray(
        array $data,
        int $total,
        int $page,
        int $perPage,
        ?string $message = null
    ): self {
        $response = new self;
        $response->success = true;
        $response->data = $data;
        $response->message = $message;
        $response->code = ApiResponseCode::SUCCESS;
        $response->pagination = [
            'type' => 'offset',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            'has_more_pages' => $perPage > 0 && $page < (int) ceil($total / $perPage),
        ];

        return $response;
    }

    public static function deleted(?string $message = null): self
    {
        $response = new self;
        $response->success = true;
        $response->message = $message ?? ApiResponseCode::DELETED->defaultMessage();
        $response->code = ApiResponseCode::DELETED;

        return $response;
    }

    public function withMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function withCode(ApiResponseCode $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function toResponse(): JsonResponse
    {
        $payload = [
            'success' => $this->success,
        ];

        if ($this->success) {
            $payload['data'] = $this->data;
            if ($this->message !== null) {
                $payload['message'] = $this->message;
            }
            if ($this->pagination !== null) {
                $payload['pagination'] = $this->pagination;
            }
        } else {
            $payload['error'] = $this->error;
        }

        return new JsonResponse(
            data: $payload,
            status: $this->code?->httpStatus() ?? 200
        );
    }
}
