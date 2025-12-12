<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Health\Checkers\PostgresHealthChecker;
use App\Domain\Health\Checkers\RedisHealthChecker;
use App\Domain\Health\Services\HealthCheckService;
use Illuminate\Support\ServiceProvider;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HealthCheckService::class, function ($app) {
            $service = new HealthCheckService;

            // PostgreSQL Health Checker 등록
            if (config('health.checkers.postgres.enabled', true)) {
                $service->register(new PostgresHealthChecker(
                    connection: config('health.checkers.postgres.connection', 'pgsql'),
                ));
            }

            // Redis Health Checker 등록
            if (config('health.checkers.redis.enabled', true)) {
                $service->register(new RedisHealthChecker(
                    connection: config('health.checkers.redis.connection', 'default'),
                ));
            }

            // 추후 MongoDB, Kafka 등 추가 시 여기에 등록
            // if (config('health.checkers.mongodb.enabled', false)) {
            //     $service->register(new MongoDbHealthChecker(...));
            // }

            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
}
