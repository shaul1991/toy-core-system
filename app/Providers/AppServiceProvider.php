<?php

namespace App\Providers;

use App\Repositories\CachedTimerRepository;
use App\Repositories\EloquentTimerRepository;
use App\Repositories\FileRepositoryInterface;
use App\Repositories\MinioFileRepository;
use App\Repositories\TimerRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // EloquentTimerRepository를 싱글톤으로 등록
        $this->app->singleton(EloquentTimerRepository::class);

        // TimerRepositoryInterface는 CachedTimerRepository로 바인딩 (캐시 레이어 적용)
        $this->app->bind(
            TimerRepositoryInterface::class,
            CachedTimerRepository::class
        );

        // FileRepositoryInterface는 MinioFileRepository로 바인딩
        $this->app->bind(
            FileRepositoryInterface::class,
            MinioFileRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
