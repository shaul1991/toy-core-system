<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 임시 phpinfo 페이지 (디버깅용, production 환경 제외)
if (! app()->isProduction()) {
    Route::get('/phpinfo', function () {
        phpinfo();
    });
}
