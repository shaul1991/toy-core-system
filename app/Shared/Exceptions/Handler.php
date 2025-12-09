<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponse;
use App\Shared\Http\ApiResponseCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * 예외 핸들러
 *
 * 도메인 예외 및 Laravel 기본 예외를 API 응답으로 자동 변환합니다.
 */
final class Handler
{
    /**
     * 예외 핸들러 설정
     */
    public static function configure(Exceptions $exceptions): void
    {
        // API 요청인 경우에만 JSON 응답 렌더링 적용
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->expectsJson() || $request->is('api/*');
        });

        // 도메인 예외 렌더링
        $exceptions->render(function (DomainException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderDomainException($e);
        });

        // Laravel ValidationException 렌더링
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderValidationException($e);
        });

        // Laravel AuthenticationException 렌더링
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderAuthenticationException($e);
        });

        // Laravel AuthorizationException 렌더링
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderAuthorizationException($e);
        });

        // ModelNotFoundException 렌더링
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderModelNotFoundException($e);
        });

        // NotFoundHttpException 렌더링
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderNotFoundHttpException($e);
        });

        // 기타 HttpException 렌더링
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return self::renderHttpException($e);
        });

        // 기타 모든 예외 렌더링 (production에서만)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            // 개발 환경에서는 기본 Laravel 에러 페이지 표시
            if (config('app.debug')) {
                return null;
            }

            return self::renderGenericException($e);
        });
    }

    /**
     * 도메인 예외를 JSON 응답으로 변환
     */
    private static function renderDomainException(DomainException $e): JsonResponse
    {
        return ApiResponse::error(
            $e->getResponseCode(),
            $e->getMessage(),
            $e->getDetails()
        )->toResponse();
    }

    /**
     * Laravel ValidationException을 JSON 응답으로 변환
     */
    private static function renderValidationException(ValidationException $e): JsonResponse
    {
        return ApiResponse::error(
            ApiResponseCode::VALIDATION_ERROR,
            $e->getMessage(),
            $e->errors()
        )->toResponse();
    }

    /**
     * AuthenticationException을 JSON 응답으로 변환
     */
    private static function renderAuthenticationException(AuthenticationException $e): JsonResponse
    {
        return ApiResponse::error(
            ApiResponseCode::UNAUTHORIZED,
            $e->getMessage() ?: ApiResponseCode::UNAUTHORIZED->defaultMessage()
        )->toResponse();
    }

    /**
     * AuthorizationException을 JSON 응답으로 변환
     */
    private static function renderAuthorizationException(AuthorizationException $e): JsonResponse
    {
        return ApiResponse::error(
            ApiResponseCode::FORBIDDEN,
            $e->getMessage() ?: ApiResponseCode::FORBIDDEN->defaultMessage()
        )->toResponse();
    }

    /**
     * ModelNotFoundException을 JSON 응답으로 변환
     */
    private static function renderModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $modelClass = class_basename($e->getModel());

        return ApiResponse::error(
            ApiResponseCode::NOT_FOUND,
            "{$modelClass}(을)를 찾을 수 없습니다."
        )->toResponse();
    }

    /**
     * NotFoundHttpException을 JSON 응답으로 변환
     */
    private static function renderNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return ApiResponse::error(
            ApiResponseCode::NOT_FOUND,
            $e->getMessage() ?: ApiResponseCode::NOT_FOUND->defaultMessage()
        )->toResponse();
    }

    /**
     * HttpException을 JSON 응답으로 변환
     */
    private static function renderHttpException(HttpException $e): JsonResponse
    {
        $code = self::httpStatusToResponseCode($e->getStatusCode());

        return ApiResponse::error(
            $code,
            $e->getMessage() ?: $code->defaultMessage()
        )->toResponse();
    }

    /**
     * 일반 예외를 JSON 응답으로 변환 (production 환경용)
     */
    private static function renderGenericException(Throwable $e): JsonResponse
    {
        return ApiResponse::error(
            ApiResponseCode::INTERNAL_ERROR
        )->toResponse();
    }

    /**
     * HTTP 상태 코드를 ApiResponseCode로 변환
     */
    private static function httpStatusToResponseCode(int $statusCode): ApiResponseCode
    {
        return match ($statusCode) {
            400 => ApiResponseCode::BAD_REQUEST,
            401 => ApiResponseCode::UNAUTHORIZED,
            403 => ApiResponseCode::FORBIDDEN,
            404 => ApiResponseCode::NOT_FOUND,
            409 => ApiResponseCode::CONFLICT,
            429 => ApiResponseCode::TOO_MANY_REQUESTS,
            503 => ApiResponseCode::SERVICE_UNAVAILABLE,
            default => ApiResponseCode::INTERNAL_ERROR,
        };
    }
}
