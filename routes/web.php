<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

// Test routes - remove after fixing API routes
// Route::prefix('test/events')->group(function () {
//     Route::post('/', [EventController::class, 'store']);
//     Route::get('/', [EventController::class, 'index']);
//     Route::get('/locations', [EventController::class, 'getLocations']);
// });