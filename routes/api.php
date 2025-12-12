<?php

use App\Domain\Auth\Controllers\AuthController;
use App\Domain\Auth\Controllers\SocialAuthController;
use App\Domain\Health\Controllers\HealthController;
use App\Domain\UserActivity\Controllers\UserActivityController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
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

    // 인증 필요 엔드포인트
    Route::middleware('auth:api')->group(function () {
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
Route::prefix('user-activity')->group(function () {
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
