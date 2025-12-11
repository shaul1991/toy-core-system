<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 기본 설정
    |--------------------------------------------------------------------------
    */

    // 최대 재시도 횟수
    'max_retries' => env('NOTIFICATION_MAX_RETRIES', 3),

    // 기본 큐 이름
    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),

    // 로그 모드 강제 사용 (production이 아니면 자동으로 true)
    'force_log_mode' => env('NOTIFICATION_FORCE_LOG_MODE', env('APP_ENV') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | 묶음 발송 설정
    |--------------------------------------------------------------------------
    */
    'batch' => [
        // 기본 묶음 간격 (초)
        'default_window' => env('NOTIFICATION_BATCH_DEFAULT_WINDOW', 3600),

        // 최소 묶음 간격 (초)
        'min_window' => 60,

        // 최대 묶음 간격 (초)
        'max_window' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | 채널 설정
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'email' => [
            'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
            'from' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('MAIL_FROM_NAME', 'Notification'),
        ],

        'sms' => [
            'enabled' => env('NOTIFICATION_SMS_ENABLED', true),
            'provider' => env('NOTIFICATION_SMS_PROVIDER', 'log'), // log, twilio, nhn, aligo
            'api_url' => env('NOTIFICATION_SMS_API_URL'),
            'api_key' => env('NOTIFICATION_SMS_API_KEY'),
            'from' => env('NOTIFICATION_SMS_FROM'),
            'timeout' => env('NOTIFICATION_SMS_TIMEOUT', 10), // HTTP 요청 타임아웃 (초)
        ],

        'slack' => [
            'enabled' => env('NOTIFICATION_SLACK_ENABLED', true),
            'provider' => env('NOTIFICATION_SLACK_PROVIDER', 'webhook'), // log, webhook
            'timeout' => env('NOTIFICATION_SLACK_TIMEOUT', 5), // HTTP 요청 타임아웃 (초)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 스케줄러 설정
    |--------------------------------------------------------------------------
    */
    'scheduler' => [
        // 예약 알림 처리 주기 (분)
        'scheduled_interval' => env('NOTIFICATION_SCHEDULED_INTERVAL', 1),

        // 묶음 알림 처리 주기 (분)
        'batched_interval' => env('NOTIFICATION_BATCHED_INTERVAL', 5),

        // 한 번에 처리할 최대 알림 수
        'batch_limit' => env('NOTIFICATION_BATCH_LIMIT', 100),
    ],
];
