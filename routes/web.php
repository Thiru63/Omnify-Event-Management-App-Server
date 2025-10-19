<?php

use App\Http\Controllers\WelcomeController;
use App\Http\Requests\CreateEventRequest;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

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


// In routes/web.php
Route::get('/debug-exact-conversion', function () {
    // Test with your exact API input
    $testInput = [
        'start_time' => '2025-12-20 10:00:00',
        'end_time' => '2025-12-20 17:00:00', 
        'timezone' => 'Asia/Kolkata'
    ];

    $results = [];

    foreach (['start_time', 'end_time'] as $field) {
        $input = $testInput[$field];
        $timezone = $testInput['timezone'];

        // Method 1: Current logic (what you're using)
        $method1 = Carbon::createFromFormat('Y-m-d H:i:s', $input, $timezone)
            ->setTimezone('UTC')
            ->format('Y-m-d H:i:s');

        // Method 2: Alternative approach
        $method2 = Carbon::parse($input)
            ->setTimezone($timezone)
            ->setTimezone('UTC')
            ->format('Y-m-d H:i:s');

        // Method 3: Manual calculation (for verification)
        $inputCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $input, $timezone);
        $manualUTC = $inputCarbon->copy()->setTimezone('UTC');

        $results[$field] = [
            'input' => $input . ' ' . $timezone,
            'method_1_result' => $method1,
            'method_2_result' => $method2, 
            'manual_calculation' => $manualUTC->format('Y-m-d H:i:s P'),
            'expected_utc' => $inputCarbon->copy()->subHours(5)->subMinutes(30)->format('Y-m-d H:i:s') . ' UTC',
            'all_methods_match' => $method1 === $method2 && $method1 === $manualUTC->format('Y-m-d H:i:s')
        ];
    }

    return response()->json([
        'conversion_test' => $results,
        'expected_behavior' => [
            'input_10:00_ist' => '2025-12-20 10:00:00 Asia/Kolkata',
            'should_store_as' => '2025-12-20 04:30:00 UTC',
            'input_17:00_ist' => '2025-12-20 17:00:00 Asia/Kolkata', 
            'should_store_as' => '2025-12-20 11:30:00 UTC'
        ],
        'your_actual_storage' => [
            'start_time' => '2025-12-19 23:00:00 UTC',
            'end_time' => '2025-12-20 06:00:00 UTC'
        ]
    ]);
});
// In routes/web.php
Route::get('/debug-server-timezone', function () {
    return response()->json([
        'server_configuration' => [
            'app_timezone' => config('app.timezone'),
            'db_timezone' => config('database.timezone', 'not set'),
            'php_timezone' => date_default_timezone_get(),
            'current_server_time' => now()->format('Y-m-d H:i:s P'),
            'current_utc_time' => now()->setTimezone('UTC')->format('Y-m-d H:i:s P'),
            'current_ist_time' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s P')
        ],
        'carbon_test' => [
            'test_input' => '2025-12-20 10:00:00',
            'parsed_default' => Carbon::parse('2025-12-20 10:00:00')->format('Y-m-d H:i:s P'),
            'parsed_with_ist' => Carbon::parse('2025-12-20 10:00:00')->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s P'),
            'created_with_ist' => Carbon::createFromFormat('Y-m-d H:i:s', '2025-12-20 10:00:00', 'Asia/Kolkata')->format('Y-m-d H:i:s P')
        ]
    ]);
});

// Root endpoint - Welcome page
Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');

// API base endpoint
Route::get('/api', [WelcomeController::class, 'apiBase'])->name('api.base');

// Health check endpoint
Route::get('/health', [WelcomeController::class, 'health'])->name('health');
Route::get('/up', [WelcomeController::class, 'health'])->name('up');

// API Documentation redirect
Route::get('/docs', function () {
    return redirect('/swagger-live');
})->name('docs');

Route::get('/documentation', function () {
    return redirect('/swagger-live');
})->name('documentation');

Route::get('/api/documentation', function () {
    return redirect('/swagger-live');
})->name('documentation2');

// Fallback for undefined routes - return JSON response
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found',
        'error' => 'The requested route does not exist',
        'available_routes' => [
            'welcome' => url('/'),
            'api_base' => url('/api'),
            'documentation' => url('/swagger-live'),
            'health_check' => url('/up'),
            'events' => url('/api/events')
        ]
    ], 404);
});