<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Admin routes FIRST so /movies/create is not swallowed by /movies/{movie}.
Route::middleware(['throttle:60,1', 'admin'])->group(function () {
    Route::resource('movies', MovieController::class)->except(['index', 'show']);
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Home page route - Passing both all movies and incomplete movies
Route::get('/', function () {
    // 1. All latest movies (for existing rows)
    $movies = \App\Models\Movie::latest()->take(24)->get();
    
    // 2. Only Incomplete movies (For the new "Watch the Trailers" row)
    $upcomingMovies = \App\Models\Movie::latest()
        ->get()
        ->filter(function($movie) {
            return $movie->isIncomplete();
        })
        ->take(12);
        
    return view('welcome', compact('movies', 'upcomingMovies'));
})->name('home');

Route::resource('movies', MovieController::class)->only(['index', 'show']);