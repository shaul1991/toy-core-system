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

        'mongodb' => [
            'enabled' => env('HEALTH_CHECK_MONGODB_ENABLED', true),
            'host' => env('MONGODB_HOST'),
            'port' => env('MONGODB_PORT', 27017),
            'database' => env('MONGODB_DATABASE', 'admin'),
            'username' => env('MONGODB_USERNAME'),
            'password' => env('MONGODB_PASSWORD'),
        ],

        'minio' => [
            'enabled' => env('HEALTH_CHECK_MINIO_ENABLED', true),
            'disk' => env('HEALTH_CHECK_MINIO_DISK', 'minio-public'),
        ],

        // 추후 추가 예정
        // 'kafka' => [
        //     'enabled' => env('HEALTH_CHECK_KAFKA_ENABLED', false),
        //     'brokers' => env('HEALTH_CHECK_KAFKA_BROKERS', 'localhost:9092'),
        // ],
    ],
];
