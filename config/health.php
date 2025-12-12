<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | 각 서비스별 Health Check 설정을 관리합니다.
    | 새로운 서비스(MongoDB, Kafka 등)를 추가할 때 여기에 설정을 추가하세요.
    |
    */

    'checkers' => [
        'postgres' => [
            'enabled' => env('HEALTH_CHECK_POSTGRES_ENABLED', true),
            'connection' => env('HEALTH_CHECK_POSTGRES_CONNECTION', 'pgsql'),
        ],

        'redis' => [
            'enabled' => env('HEALTH_CHECK_REDIS_ENABLED', true),
            'connection' => env('HEALTH_CHECK_REDIS_CONNECTION', 'default'),
        ],

        // 추후 추가 예정
        // 'mongodb' => [
        //     'enabled' => env('HEALTH_CHECK_MONGODB_ENABLED', false),
        //     'connection' => env('HEALTH_CHECK_MONGODB_CONNECTION', 'mongodb'),
        // ],

        // 'kafka' => [
        //     'enabled' => env('HEALTH_CHECK_KAFKA_ENABLED', false),
        //     'brokers' => env('HEALTH_CHECK_KAFKA_BROKERS', 'localhost:9092'),
        // ],
    ],
];
