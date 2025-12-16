<?php

use App\Http\Controllers\PostViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Post Web Routes
|--------------------------------------------------------------------------
*/
Route::prefix('posts')->group(function () {
    // Public routes
    Route::get('/', [PostViewController::class, 'index'])->name('posts.index');
    Route::get('/{slug}', [PostViewController::class, 'show'])->name('posts.show');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::get('/create', [PostViewController::class, 'create'])->name('posts.create');
        Route::get('/{id}/edit', [PostViewController::class, 'edit'])->name('posts.edit');
    });
});
