<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\AttendeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Event management routes
Route::prefix('events')->group(function () {
    // Event management
    Route::post('/', [EventController::class, 'store'])->name('store');
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/locations', [EventController::class, 'getLocations'])->name('locations');
    
    // Attendee management
    Route::post('/{event_id}/register', [AttendeeController::class, 'register'])
        ->name('register')
        ->middleware('throttle:event-registration');
        
    Route::get('/{event_id}/attendees', [AttendeeController::class, 'index'])
        ->name('attendees');
});