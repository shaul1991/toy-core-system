<?php

use App\Bff\Controllers\AuthController;
use App\Bff\Controllers\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (BFF - Backend For Frontend)
|--------------------------------------------------------------------------
|
| 프론트엔드에서 직접 호출하는 외부 공개 API입니다.
| JWT 인증을 처리하고 Core Service(Internal)를 호출합니다.
|
| 아키텍처:
|   Frontend → API (/api/*) → Internal (/internal/*)
|
| 경로: /api/*
|
*/

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| 인증 관련 BFF 엔드포인트
| - 소셜 로그인/회원가입
| - 토큰 관리 (발급, 갱신, 검증)
| - 세션 관리 (로그아웃)
|
*/
Route::prefix('auth')->group(function () {
    // 공개 엔드포인트 (인증 불필요)

    // 소셜 로그인 - OAuth 리다이렉트
    Route::get('{provider}/redirect', [AuthController::class, 'redirect'])
        ->where('provider', 'github|naver|kakao');

    // 소셜 로그인 - OAuth 콜백
    Route::get('{provider}/callback', [AuthController::class, 'callback'])
        ->where('provider', 'github|naver|kakao')
        ->middleware('throttle:10,1');

    // 토큰 갱신
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:30,1');

    // 토큰 검증
    Route::post('validate', [AuthController::class, 'validate']);

    // 인증 필요 엔드포인트
    Route::middleware('bff.auth')->group(function () {
        // 현재 사용자 정보
        Route::get('me', [AuthController::class, 'me'])
            ->middleware('throttle:60,1');

        // 로그아웃
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('throttle:10,1');

        // 전체 세션 로그아웃
        Route::post('logout-all', [AuthController::class, 'logoutAll'])
            ->middleware('throttle:10,1');

        // 소셜 계정 관리
        Route::get('social-accounts', [AuthController::class, 'socialAccounts'])
            ->middleware('throttle:60,1');

        // 소셜 계정 연동
        Route::get('{provider}/link', [AuthController::class, 'link'])
            ->where('provider', 'github|naver|kakao')
            ->middleware('throttle:5,1');

        // 소셜 계정 연동 해제
        Route::delete('{provider}/unlink', [AuthController::class, 'unlink'])
            ->where('provider', 'github|naver|kakao')
            ->middleware('throttle:5,1');
    });
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
|
| 게시물 관련 BFF 엔드포인트
| - 게시물 CRUD
| - 발행 관리
| - 통계 조회
|
*/
Route::prefix('posts')->group(function () {
    // 공개 엔드포인트 (인증 불필요)

    // 발행된 게시물 목록 조회
    Route::get('published', [PostController::class, 'published'])
        ->middleware('throttle:60,1');

    // 발행된 게시물 상세 조회 (slug)
    Route::get('slug/{slug}/published', [PostController::class, 'showPublishedBySlug'])
        ->middleware('throttle:60,1');

    // 인증 필요 엔드포인트
    Route::middleware('bff.auth')->group(function () {
        // CRUD
        Route::get('/', [PostController::class, 'index'])
            ->middleware('throttle:60,1');

        Route::post('/', [PostController::class, 'store'])
            ->middleware('throttle:30,1');

        Route::get('{id}', [PostController::class, 'show'])
            ->where('id', '[0-9]+')
            ->middleware('throttle:60,1');

        Route::put('{id}', [PostController::class, 'update'])
            ->where('id', '[0-9]+')
            ->middleware('throttle:30,1');

        Route::delete('{id}', [PostController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->middleware('throttle:10,1');

        // slug 조회
        Route::get('slug/{slug}', [PostController::class, 'showBySlug'])
            ->middleware('throttle:60,1');

        // 발행 관리
        Route::post('{id}/publish', [PostController::class, 'publish'])
            ->where('id', '[0-9]+')
            ->middleware('throttle:10,1');

        Route::post('{id}/unpublish', [PostController::class, 'unpublish'])
            ->where('id', '[0-9]+')
            ->middleware('throttle:10,1');

        // 통계
        Route::get('stats', [PostController::class, 'stats'])
            ->middleware('throttle:60,1');
    });
});
