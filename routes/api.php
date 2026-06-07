<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Catalog
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/menus', [MenuController::class, 'index']);
    Route::patch('/menus/{menu}/availability', [MenuController::class, 'toggleAvailability']);

    // Orders ⚠️ stats route harus DI ATAS {order} biar ga ke-match "stats" sebagai ID
    Route::get('/orders/stats', [OrderController::class, 'stats']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/void', [OrderController::class, 'void']);
});