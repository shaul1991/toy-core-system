<?php

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
