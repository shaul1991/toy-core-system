<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Health\Checkers\MinioHealthChecker;
use App\Domain\Health\Checkers\MongoDbHealthChecker;
use App\Domain\Health\Checkers\PostgresHealthChecker;
use App\Domain\Health\Checkers\RedisHealthChecker;
use App\Domain\Health\Services\HealthCheckService;
use Illuminate\Support\ServiceProvider;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HealthCheckService::class, function () {
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

            // MongoDB Health Checker 등록
            if (config('health.checkers.mongodb.enabled', false)) {
                $service->register(new MongoDbHealthChecker(
                    host: config('health.checkers.mongodb.host'),
                    port: (int) config('health.checkers.mongodb.port', 27017),
                    database: config('health.checkers.mongodb.database', 'admin'),
                    username: config('health.checkers.mongodb.username'),
                    password: config('health.checkers.mongodb.password'),
                ));
            }

            // MinIO Health Checker 등록
            if (config('health.checkers.minio.enabled', false)) {
                $service->register(new MinioHealthChecker(
                    disk: config('health.checkers.minio.disk', 'minio-public'),
                ));
            }

            // 추후 Kafka 등 추가 시 여기에 등록

            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
}
