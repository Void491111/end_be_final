<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve menu images langsung dari storage/app/public (bypass symlink)
Route::get('/img/{path}', function (string $path) {
    $fullPath = storage_path('app/private/' . $path);
    abort_unless(file_exists($fullPath), 404);
    return response()->file($fullPath);
})->where('path', '.*');