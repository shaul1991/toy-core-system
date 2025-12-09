<?php

namespace App\Providers;

use App\Repositories\EloquentTimerRepository;
use App\Repositories\TimerRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TimerRepositoryInterface::class,
            EloquentTimerRepository::class
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
