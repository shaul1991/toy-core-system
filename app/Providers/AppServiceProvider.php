<?php

namespace App\Providers;

use App\Repositories\CachedFileRepository;
use App\Repositories\CachedTimerRepository;
use App\Repositories\EloquentNotificationLogRepository;
use App\Repositories\EloquentNotificationQueueRepository;
use App\Repositories\EloquentTimerRepository;
use App\Repositories\FileRepositoryInterface;
use App\Repositories\MinioFileRepository;
use App\Repositories\NotificationLogRepositoryInterface;
use App\Repositories\NotificationQueueRepositoryInterface;
use App\Repositories\TimerRepositoryInterface;
use App\Services\Notification\BatchAggregator;
use App\Services\Notification\Channels\EmailChannel;
use App\Services\Notification\Channels\SlackChannel;
use App\Services\Notification\Channels\SmsChannel;
use App\Services\Notification\NotificationDispatcher;
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

        // Notification Repositories
        $this->app->bind(
            NotificationQueueRepositoryInterface::class,
            EloquentNotificationQueueRepository::class
        );

        $this->app->bind(
            NotificationLogRepositoryInterface::class,
            EloquentNotificationLogRepository::class
        );

        // Notification Services
        $this->app->singleton(BatchAggregator::class);

        $this->app->singleton(NotificationDispatcher::class, function ($app) {
            $dispatcher = new NotificationDispatcher(
                $app->make(NotificationQueueRepositoryInterface::class),
                $app->make(NotificationLogRepositoryInterface::class),
                $app->make(BatchAggregator::class),
            );

            // 채널 등록
            $config = config('notification.channels', []);

            if ($config['email']['enabled'] ?? true) {
                $dispatcher->registerChannel(new EmailChannel($config['email'] ?? []));
            }

            if ($config['sms']['enabled'] ?? true) {
                $dispatcher->registerChannel(new SmsChannel($config['sms'] ?? []));
            }

            if ($config['slack']['enabled'] ?? true) {
                $dispatcher->registerChannel(new SlackChannel($config['slack'] ?? []));
            }

            return $dispatcher;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
