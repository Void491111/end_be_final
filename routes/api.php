<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected (perlu Bearer token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Catalog
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/menus', [MenuController::class, 'index']);
    Route::patch('/menus/{menu}/availability', [MenuController::class, 'toggleAvailability']);
});