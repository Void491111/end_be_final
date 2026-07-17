<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Public — buat customer self-order via QR (no auth)
Route::prefix('public')->group(function () {
    Route::get('/recommendations', [MenuController::class, 'recommendations']);
});

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard/weekly-revenue', [DashboardController::class, 'weeklyRevenue']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/recommendations', [MenuController::class, 'recommendations']);
    Route::patch('/menus/{menu}/availability', [MenuController::class, 'toggleAvailability']);

    Route::get('/orders/stats', [OrderController::class, 'stats']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/void', [OrderController::class, 'void']);
});