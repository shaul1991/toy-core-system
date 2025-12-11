<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 임시 phpinfo 페이지 (디버깅용)
Route::get('/phpinfo', function () {
    phpinfo();
});
