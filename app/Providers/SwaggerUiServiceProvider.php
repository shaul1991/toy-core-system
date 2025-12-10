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
            // 프로덕션 환경에서만 인증 필요
            if (app()->environment('production')) {
                return in_array(optional($user)->email, [
                    //
                ]);
            }

            // 프로덕션이 아닌 환경에서는 항상 접근 허용
            return true;
        });
    }
}
