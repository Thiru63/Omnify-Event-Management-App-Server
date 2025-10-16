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

// DIRECT JSON ROUTE - This will definitely work
Route::get('/api-docs.json', function() {
    $jsonPath = storage_path('api-docs/api-docs.json');
    
    if (file_exists($jsonPath)) {
        return response()->file($jsonPath, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
    
    // If file doesn't exist, return simple JSON
    return response()->json([
        "openapi" => "3.0.0",
        "info" => [
            "title" => "Event Management API",
            "description" => "API for managing events and attendee registrations", 
            "version" => "1.0.0"
        ],
        "servers" => [
            [
                "url" => "https://omnify-event-management-app-server.onrender.com/api",
                "description" => "Production Server"
            ]
        ],
        "paths" => []
    ]);
});

// GUARANTEED WORKING SWAGGER UI
Route::get('/swagger-live', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Event Management API - LIVE</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
        <script>
            window.ui = SwaggerUIBundle({
                url: "https://omnify-event-management-app-server.onrender.com/api-docs.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        </script>
    </body>
    </html>
    ';
});

// Your existing event and attendee routes below...

// routes/web.php
Route::get('/api-docs-fresh.json', function() {
    $jsonPath = storage_path('api-docs/api-docs.json');
    
    if (!file_exists($jsonPath)) {
        return response()->json(['error' => 'JSON not found'], 404);
    }
    
    $jsonContent = file_get_contents($jsonPath);
    
    return response($jsonContent)
        ->header('Content-Type', 'application/json')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
});

Route::get('/swagger-fresh', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Event Management API - FRESH</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
        <script>
            window.ui = SwaggerUIBundle({
                url: "https://omnify-event-management-app-server.onrender.com/api-docs-fresh.json?t=" + Date.now(),
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        </script>
    </body>
    </html>
    ';
});

Route::get('/debug-json-content', function() {
    $jsonPath = storage_path('api-docs/api-docs.json');
    
    if (!file_exists($jsonPath)) {
        return response()->json(['error' => 'JSON file not found'], 404);
    }
    
    $jsonContent = file_get_contents($jsonPath);
    $data = json_decode($jsonContent, true);
    
    // Return the exact servers array
    return [
        'servers' => $data['servers'] ?? 'No servers key',
        'full_json_url' => 'https://omnify-event-management-app-server.onrender.com/api-docs.json',
        'json_first_500_chars' => substr($jsonContent, 0, 500)
    ];
});

Route::get('/check-json-structure', function() {
    $jsonPath = storage_path('api-docs/api-docs.json');
    
    if (!file_exists($jsonPath)) {
        return response()->json(['error' => 'JSON file not found'], 404);
    }
    
    $jsonContent = file_get_contents($jsonPath);
    $data = json_decode($jsonContent, true);
    
    return [
        'servers' => $data['servers'] ?? 'No servers',
        'host' => $data['host'] ?? 'No host',
        'basePath' => $data['basePath'] ?? 'No basePath',
        'schemes' => $data['schemes'] ?? 'No schemes',
        'has_localhost' => str_contains($jsonContent, 'localhost'),
        'has_8000' => str_contains($jsonContent, '8000')
    ];
});