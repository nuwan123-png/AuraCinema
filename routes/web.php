<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

// Admin routes FIRST so /movies/create is not swallowed by /movies/{movie}.
Route::middleware(['throttle:60,1', 'admin'])->group(function () {
    Route::get('/admin/stats', [StatsController::class, 'index'])->name('admin.stats');
    Route::get('/admin/incomplete', [MovieController::class, 'incomplete'])->name('movies.incomplete');
    Route::resource('movies', MovieController::class)->except(['index', 'show']);
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::delete('/requests/{movieRequest}', [RequestController::class, 'destroy'])->name('requests.destroy');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);
// Public pages: counted by the visitor stats (see TrackVisit).
Route::middleware('track')->group(function () {
    Route::get('/', [MovieController::class, 'index'])->name('home');
    Route::get('/suggestions', [MovieController::class, 'suggestions'])->name('suggestions');
    Route::get('/request', [RequestController::class, 'create'])->name('request.create');
    Route::resource('movies', MovieController::class)->only(['index', 'show']);
});
Route::get('/search/live', [MovieController::class, 'liveSearch'])->middleware('throttle:60,1')->name('search.live');
Route::post('/request', [RequestController::class, 'store'])->middleware('throttle:5,1')->name('request.store');
Route::get('/api/v1/movies/{movie}', [MovieController::class, 'apiShow'])->middleware('throttle:60,1');
