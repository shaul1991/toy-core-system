<?php

declare(strict_types=1);

namespace App\Domain\Health\Controllers;

use App\Domain\Health\Services\HealthCheckService;
use App\Http\Controllers\Controller;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    use ApiResponsable;

    public function __construct(
        private readonly HealthCheckService $healthCheckService,
    ) {}

    /**
     * 전체 서비스 Health Check
     */
    public function index(): JsonResponse
    {
        $result = $this->healthCheckService->checkAll();

        $statusCode = $result['status'] === 'healthy'
            ? ApiResponseCode::SUCCESS
            : ApiResponseCode::SERVICE_UNAVAILABLE;

        return ApiResponse::success($result)
            ->withCode($statusCode)
            ->toResponse();
    }

    /**
     * 특정 서비스 Health Check
     */
    public function show(string $service): JsonResponse
    {
        $result = $this->healthCheckService->check($service);

        if ($result === null) {
            return $this->notFoundResponse(
                "Service '{$service}' not found. Available services: "
                .implode(', ', $this->healthCheckService->getRegisteredServices())
            );
        }

        $statusCode = $result->healthy
            ? ApiResponseCode::SUCCESS
            : ApiResponseCode::SERVICE_UNAVAILABLE;

        return ApiResponse::success($result)
            ->withCode($statusCode)
            ->toResponse();
    }
}
