<?php

namespace App\Providers;

use App\Repositories\CachedFileRepository;
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

        // MinioFileRepository를 싱글톤으로 등록
        $this->app->singleton(MinioFileRepository::class);

        // FileRepositoryInterface는 CachedFileRepository로 바인딩 (캐시 레이어 적용)
        $this->app->bind(
            FileRepositoryInterface::class,
            CachedFileRepository::class
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
