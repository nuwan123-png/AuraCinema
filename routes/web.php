<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Admin routes FIRST so /movies/create is not swallowed by /movies/{movie}.
Route::middleware(['throttle:60,1', 'admin'])->group(function () {
    Route::get('/admin/incomplete', [MovieController::class, 'incomplete'])->name('movies.incomplete');
    Route::resource('movies', MovieController::class)->except(['index', 'show']);
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::delete('/requests/{movieRequest}', [RequestController::class, 'destroy'])->name('requests.destroy');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/', [MovieController::class, 'index'])->name('home');
Route::get('/search/live', [MovieController::class, 'liveSearch'])->middleware('throttle:60,1')->name('search.live');
Route::get('/suggestions', [MovieController::class, 'suggestions'])->name('suggestions');
Route::get('/request', [RequestController::class, 'create'])->name('request.create');
Route::post('/request', [RequestController::class, 'store'])->middleware('throttle:5,1')->name('request.store');
Route::resource('movies', MovieController::class)->only(['index', 'show']);
