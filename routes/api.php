<?php

use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('timers')->group(function () {
    Route::get('{key}', [TimerController::class, 'show']);
    Route::put('{key}', [TimerController::class, 'upsert']);
    Route::delete('{key}', [TimerController::class, 'destroy']);
});
