<?php

use App\Domain\Auth\Controllers\AuthController;
use App\Domain\Auth\Controllers\SocialAuthController;
use App\Domain\Health\Controllers\HealthController;
use App\Domain\Post\Controllers\PostController;
use App\Domain\UserActivity\Controllers\UserActivityController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Routes (Core Service)
|--------------------------------------------------------------------------
|
| 내부 서비스 전용 API 라우트입니다.
| BFF 또는 다른 내부 서비스에서 호출합니다.
|
| 경로: /internal/*
|
*/

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
*/
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'index']);
    Route::get('/{service}', [HealthController::class, 'show']);
});

Route::prefix('timers')->group(function () {
    Route::get('{key}', [TimerController::class, 'show']);
    Route::put('{key}', [TimerController::class, 'upsert']);
    Route::delete('{key}', [TimerController::class, 'destroy']);
});

Route::prefix('files')->group(function () {
    Route::get('/', [FileController::class, 'index']);
    Route::post('/', [FileController::class, 'store']);
    Route::get('{id}', [FileController::class, 'show']);
    Route::get('{id}/download', [FileController::class, 'download']);
    Route::delete('{id}', [FileController::class, 'destroy']);
    Route::delete('{id}/force', [FileController::class, 'forceDestroy']);
    Route::patch('{id}/visibility', [FileController::class, 'updateVisibility']);
    Route::post('{id}/temporary-url', [FileController::class, 'temporaryUrl']);
});

Route::prefix('notifications')->group(function () {
    // 채널 목록
    Route::get('channels', [NotificationController::class, 'channels']);

    // 알림 대기열
    Route::post('/', [NotificationController::class, 'store']);
    Route::get('queue', [NotificationController::class, 'indexQueue']);
    Route::get('queue/{id}', [NotificationController::class, 'showQueue']);
    Route::delete('queue/{id}', [NotificationController::class, 'cancelQueue']);
    Route::post('queue/{id}/retry', [NotificationController::class, 'retryQueue']);

    // 알림 로그
    Route::get('logs', [NotificationController::class, 'indexLogs']);
    Route::get('logs/{id}', [NotificationController::class, 'showLog']);
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // 소셜 로그인 - 공개 엔드포인트
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'github|naver|kakao');

    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'github|naver|kakao')
        ->middleware('throttle:10,1'); // 10회/분 (문서 기준)

    // 공개 엔드포인트
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:30,1');

    Route::post('validate', [AuthController::class, 'validate']);

    // 사용자 ID 필요 엔드포인트 (BFF에서 JWT 검증 후 X-User-Id 헤더로 전달)
    Route::middleware('user.id')->group(function () {
        // 사용자 정보 조회 - 60회/분
        Route::get('me', [AuthController::class, 'me'])
            ->middleware('throttle:60,1');

        // 로그아웃 - 10회/분 (문서 기준)
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('throttle:10,1');

        Route::post('logout-all', [AuthController::class, 'logoutAll'])
            ->middleware('throttle:10,1');

        // 소셜 계정 관리
        Route::get('social-accounts', [SocialAuthController::class, 'socialAccounts'])
            ->middleware('throttle:60,1');

        // 소셜 계정 연동 - 5회/분 (문서 기준)
        Route::post('{provider}/link', [SocialAuthController::class, 'link'])
            ->where('provider', 'github|naver|kakao')
            ->middleware('throttle:5,1');

        Route::delete('{provider}/unlink', [SocialAuthController::class, 'unlink'])
            ->where('provider', 'github|naver|kakao')
            ->middleware('throttle:5,1');
    });
});

/*
|--------------------------------------------------------------------------
| User Activity Routes
|--------------------------------------------------------------------------
*/
Route::prefix('user-activity')->middleware('user.id:optional')->group(function () {
    // CRUD 기본 엔드포인트
    Route::get('/', [UserActivityController::class, 'index']);
    Route::post('/', [UserActivityController::class, 'store']);
    Route::get('{id}', [UserActivityController::class, 'show'])
        ->where('id', '[a-f0-9]{24}');
    Route::put('{id}', [UserActivityController::class, 'update'])
        ->where('id', '[a-f0-9]{24}');
    Route::delete('{id}', [UserActivityController::class, 'destroy'])
        ->where('id', '[a-f0-9]{24}');

    // 사용자별 활동 조회
    Route::get('user/{userId}', [UserActivityController::class, 'byUser'])
        ->where('userId', '[0-9]+');
    Route::get('user/{userId}/stats', [UserActivityController::class, 'stats'])
        ->where('userId', '[0-9]+');
    Route::delete('user/{userId}', [UserActivityController::class, 'destroyByUser'])
        ->where('userId', '[0-9]+');
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/
Route::prefix('posts')->group(function () {
    // 공개 엔드포인트 (인증 불필요)
    Route::get('published', [PostController::class, 'published']);
    Route::get('slug/{slug}/published', [PostController::class, 'showPublishedBySlug']);

    // 인증 필요 엔드포인트
    Route::middleware('user.id')->group(function () {
        // CRUD
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store']);
        Route::get('{id}', [PostController::class, 'show'])->where('id', '[0-9]+');
        Route::put('{id}', [PostController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('{id}', [PostController::class, 'destroy'])->where('id', '[0-9]+');

        // slug 조회
        Route::get('slug/{slug}', [PostController::class, 'showBySlug']);

        // 발행 관리
        Route::post('{id}/publish', [PostController::class, 'publish'])->where('id', '[0-9]+');
        Route::post('{id}/unpublish', [PostController::class, 'unpublish'])->where('id', '[0-9]+');

        // 통계
        Route::get('stats', [PostController::class, 'stats']);
    });
});
