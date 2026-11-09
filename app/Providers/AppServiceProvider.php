<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View; // මෙය අලුතින් එක් කර ඇත

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CloudFront හරහා එන විට පමණක් (X-Forwarded-Proto: https ඇති විට) HTTPS force කිරීම
        if (
            request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->isSecure()
        ) {
            URL::forceScheme('https');
        }

        // Blade Cache Errors විසඳීම සඳහා Nav Variables Global ලෙස ලබා දීම
        View::share('globalNavGenres', ['Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller']);
        View::share('globalNavLanguages', ['Sinhala', 'English', 'Tamil', 'Hindi', 'Malayalam', 'Telugu', 'Korean']);
        View::share('globalNavTypes', ['movie' => 'Movies', 'series' => 'Drama / Series', 'anime' => 'Anime']);
    }
}