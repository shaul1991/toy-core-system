<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\UserActivity\Contracts\UserActivityRepositoryInterface;
use App\Domain\UserActivity\Repositories\MongoUserActivityRepository;
use App\Domain\UserActivity\Services\UserActivityService;
use App\Shared\Database\MongoDB\MongoConnection;
use Illuminate\Support\ServiceProvider;

class UserActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // MongoDB Connection 싱글톤 등록
        $this->app->singleton(MongoConnection::class, function () {
            return MongoConnection::fromConfig('mongodb');
        });

        // Repository 바인딩
        $this->app->bind(
            UserActivityRepositoryInterface::class,
            MongoUserActivityRepository::class
        );

        // Service 싱글톤 등록
        $this->app->singleton(UserActivityService::class, function ($app) {
            return new UserActivityService(
                $app->make(UserActivityRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
