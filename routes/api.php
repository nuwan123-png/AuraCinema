<?php

use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\WatchHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // User Account Auth
    Route::post('/user/register', [UserAuthController::class, 'register']);
    Route::post('/user/login', [UserAuthController::class, 'login']);
    Route::get('/app-update-status', [\App\Http\Controllers\Api\AppUpdateController::class, 'status']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/user/logout', [UserAuthController::class, 'logout']);

        // Flutter app call karana /api/v1/watch-history routes
        Route::get('/watch-history', [WatchHistoryController::class, 'index']);
        Route::post('/watch-history', [WatchHistoryController::class, 'store']);
        Route::delete('/watch-history/{id}', [WatchHistoryController::class, 'destroy']);

        // Backup /user/watch-history routes
        Route::get('/user/watch-history', [WatchHistoryController::class, 'index']);
        Route::post('/user/watch-history', [WatchHistoryController::class, 'store']);
        Route::delete('/user/watch-history/{id}', [WatchHistoryController::class, 'destroy']);
    });
});