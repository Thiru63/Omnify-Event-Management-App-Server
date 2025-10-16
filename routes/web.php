<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

// Test routes - remove after fixing API routes
// Route::prefix('test/events')->group(function () {
//     Route::post('/', [EventController::class, 'store']);
//     Route::get('/', [EventController::class, 'index']);
//     Route::get('/locations', [EventController::class, 'getLocations']);
// });
// In routes/web.php
Route::get('/swagger-check', function() {
    $filePath = storage_path('api-docs/api-docs.json');
    
    return [
        'file_exists' => file_exists($filePath),
        'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
        'storage_writable' => is_writable(storage_path()),
        'docs_dir_writable' => is_writable(storage_path('api-docs')),
        'file_content_sample' => file_exists($filePath) ? substr(file_get_contents($filePath), 0, 100) : 'File not found'
    ];
});

// In routes/web.php
Route::get('/swagger-assets-check', function() {
    return [
        'vendor_swagger_exists' => file_exists(base_path('vendor/swagger-api/swagger-ui')),
        'public_swagger_exists' => file_exists(public_path('vendor/l5-swagger')),
        'swagger_ui_js' => file_exists(public_path('vendor/l5-swagger/swagger-ui.js')),
        'swagger_ui_css' => file_exists(public_path('vendor/l5-swagger/swagger-ui.css')),
        'asset_url' => asset('vendor/l5-swagger/swagger-ui.js'),
    ];
});