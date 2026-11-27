<?php

use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\WatchHistoryController;

Route::prefix('v1')->group(function () {
    // ... oyage parana routes tika mehema thiyenna denna ...

    // User Account Auth
    Route::post('/user/register', [UserAuthController::class, 'register']);
    Route::post('/user/login', [UserAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/user/logout', [UserAuthController::class, 'logout']);
        Route::get('/user/watch-history', [WatchHistoryController::class, 'index']);
        Route::post('/user/watch-history', [WatchHistoryController::class, 'store']);
        Route::delete('/user/watch-history/{id}', [WatchHistoryController::class, 'destroy']);
    });
});