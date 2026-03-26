<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;

// Public routes
Route::prefix('v1')->group(function () {

    // Auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Wallet
        Route::get('/wallet',         [WalletController::class, 'show']);
        Route::post('/wallet/topup',  [WalletController::class, 'topup']);

        // Orders
        Route::get('/orders',          [OrderController::class, 'index']);
        Route::post('/orders',         [OrderController::class, 'store']);
        Route::get('/orders/{id}',     [OrderController::class, 'show']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
