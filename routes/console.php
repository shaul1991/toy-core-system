<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Notification Scheduler
|--------------------------------------------------------------------------
*/

// 예약 알림 처리 (매 분)
Schedule::command('notification:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// 묶음 알림 처리 (5분마다)
Schedule::command('notification:process-batched')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
