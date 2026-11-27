<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WatchHistoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/watch-history', [WatchHistoryController::class, 'index']);
    Route::post('/watch-history', [WatchHistoryController::class, 'store']);
    Route::delete('/watch-history/{id}', [WatchHistoryController::class, 'destroy']);
});
