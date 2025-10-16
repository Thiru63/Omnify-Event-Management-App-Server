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



// routes/web.php
Route::get('/swagger-assets-check', function() {
    return [
        'vendor_swagger_exists' => file_exists(base_path('vendor/swagger-api/swagger-ui')),
        'public_swagger_exists' => file_exists(public_path('vendor/l5-swagger')),
        'swagger_ui_js' => file_exists(public_path('vendor/l5-swagger/swagger-ui.js')),
        'swagger_ui_css' => file_exists(public_path('vendor/l5-swagger/swagger-ui.css')),
        'asset_url_js' => asset('vendor/l5-swagger/swagger-ui.js'),
        'asset_url' => asset('vendor/l5-swagger/swagger-ui.js'),
        'asset_url_css' => asset('vendor/l5-swagger/swagger-ui.css'),
        'json_url' => url('api-docs.json'),
    ];
});

// routes/web.php
Route::get('/db-test', function() {
    try {
        DB::connection()->getPdo();
        return [
            'status' => 'Connected',
            'database' => DB::connection()->getDatabaseName(),
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'Failed',
            'error' => $e->getMessage(),
        ];
    }
});

// routes/web.php
Route::get('/sqlite-test', function() {
    try {
        \DB::connection()->getPdo();
        return [
            'status' => 'Connected to SQLite',
            'database' => \DB::connection()->getDatabaseName(),
            'file_exists' => file_exists(database_path('database.sqlite')),
            'file_size' => filesize(database_path('database.sqlite')),
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'Failed',
            'error' => $e->getMessage(),
        ];
    }
});

// routes/web.php
Route::get('/swagger-debug-full', function() {
    $viewPath = resource_path('views/vendor/l5-swagger/index.blade.php');
    $viewContent = file_exists($viewPath) ? file_get_contents($viewPath) : 'View file not found';
    
    return [
        'view_file_exists' => file_exists($viewPath),
        'view_file_size' => file_exists($viewPath) ? filesize($viewPath) : 0,
        'view_contains_cdn' => str_contains($viewContent, 'unpkg.com'),
        'config_assets_path' => config('l5-swagger.defaults.swagger_ui_assets_path'),
        'json_url' => url('api-docs.json'),
        'json_exists' => file_exists(storage_path('api-docs/api-docs.json')),
    ];
});

// routes/web.php
// routes/web.php
Route::get('/swagger-fixed', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Event Management API</title>
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

Route::get('/view-debug', function() {
    $viewPath = resource_path('views/vendor/l5-swagger/index.blade.php');
    if (!file_exists($viewPath)) {
        return 'View file does not exist';
    }
    
    $content = file_get_contents($viewPath);
    return response($content)->header('Content-Type', 'text/plain');
});

// routes/web.php
Route::get('/swagger-test', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Swagger Test</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: "'.url('api-docs.json').'",
                dom_id: "#swagger-ui",
            });
        </script>
    </body>
    </html>
    ';
});

// routes/web.php
Route::get('/swagger-cdn-test', function() {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Swagger CDN Test</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: "' . url('api-docs.json') . '",
                dom_id: "#swagger-ui",
                presets: [SwaggerUIBundle.presets.apis],
                layout: "StandaloneLayout"
            });
        </script>
    </body>
    </html>
    ';
    
    return response($html)->header('Content-Type', 'text/html');
});
// Create a simple view in your views folder
// resources/views/swagger-fixed.blade.php
// Copy the HTML from the swagger-index.blade.php above

// routes/web.php
Route::get('/swagger-view-check', function() {
    $viewPath = resource_path('views/vendor/l5-swagger/index.blade.php');
    
    if (!file_exists($viewPath)) {
        return 'View file does not exist';
    }
    
    $content = file_get_contents($viewPath);
    
    return [
        'file_exists' => true,
        'contains_cdn_css' => str_contains($content, 'https://unpkg.com/swagger-ui-dist'),
        'contains_cdn_js' => str_contains($content, 'swagger-ui-bundle.js'),
        'contains_local_assets' => str_contains($content, 'docs/asset'),
    ];
});