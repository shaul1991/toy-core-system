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
            // 로컬 환경에서는 항상 접근 허용
            if (app()->environment('local', 'testing')) {
                return true;
            }

            // 프로덕션 환경에서는 인증된 사용자의 이메일 확인
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
