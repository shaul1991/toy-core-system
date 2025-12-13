<?php

use App\Shared\Exceptions\Handler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // BFF 라우트 등록 (/bff/*)
            Route::middleware('api')
                ->prefix('bff')
                ->group(base_path('routes/bff.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (for load balancer / reverse proxy SSL termination)
        $middleware->trustProxies(at: '*');

        // Domain Service용 미들웨어 등록
        $middleware->alias([
            'user.id' => \App\Http\Middleware\ExtractUserId::class,
            'bff.auth' => \App\Bff\Middleware\JwtAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry 통합
        Integration::handles($exceptions);

        // 도메인 예외 → API 응답 자동 변환
        Handler::configure($exceptions);
    })->create();
