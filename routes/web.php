<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

// Admin routes FIRST so /movies/create is not swallowed by /movies/{movie}.
Route::middleware(['throttle:60,1', 'admin'])->group(function () {
    Route::resource('movies', MovieController::class)->except(['index', 'show']);
});

Route::get('/', [MovieController::class, 'index'])->name('home');
Route::resource('movies', MovieController::class)->only(['index', 'show']);
