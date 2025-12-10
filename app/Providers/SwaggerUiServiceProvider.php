<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SwaggerUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewSwaggerUI', function ($user = null) {
            // SWAGGER_UI_ENABLED 환경변수로 접근 제어 (기본값: false)
            // .env에서 SWAGGER_UI_ENABLED=true로 설정해야만 Swagger UI에 접근 가능
            return env('SWAGGER_UI_ENABLED', false) === true;
        });
    }
}
