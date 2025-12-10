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
            if (env('SWAGGER_UI_ENABLED', false) === true) {
                return true;
            }

            // 비활성화된 경우 인증된 사용자의 이메일 확인
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
