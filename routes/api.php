<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\PublicOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Customer QR self-order (public, no auth)
Route::prefix('public')->group(function () {
    Route::get('/tables/{code}', [PublicController::class, 'validateTable']);
    Route::get('/menus', [PublicController::class, 'menus']);
    Route::get('/categories', [PublicController::class, 'categories']);
    Route::get('/recommendations', [MenuController::class, 'recommendations']);

    Route::post('/orders', [PublicOrderController::class, 'store']);
    Route::get('/orders/{id}/status', [PublicOrderController::class, 'status']);
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
