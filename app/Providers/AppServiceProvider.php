<?php

namespace App\Providers;

use App\Domain\Auth\Observers\UserObserver;
use App\Domain\Auth\Repositories\RedisRefreshTokenRepository;
use App\Domain\Auth\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Services\UserCacheService;
use App\Domain\Auth\Services\UserCacheServiceInterface;
use App\Models\User;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Kakao\KakaoExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Naver\NaverExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Auth - RefreshToken Repository
        $this->app->bind(
            RefreshTokenRepositoryInterface::class,
            RedisRefreshTokenRepository::class
        );

        // Auth - UserCache Service
        $this->app->bind(
            UserCacheServiceInterface::class,
            UserCacheService::class
        );

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
        // User 모델 옵저버 등록 (캐시 무효화)
        User::observe(UserObserver::class);

        // Socialite 확장 프로바이더 등록 (Naver, Kakao)
        Event::listen(SocialiteWasCalled::class, NaverExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, KakaoExtendSocialite::class.'@handle');
    }
}
