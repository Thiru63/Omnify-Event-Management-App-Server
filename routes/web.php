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
Route::get('/debug-events', function () {
    $events = \App\Models\Event::all();
    $upcomingEvents = \App\Models\Event::upcoming()->get();
    
    return response()->json([
        'all_events_count' => $events->count(),
        'upcoming_events_count' => $upcomingEvents->count(),
        'all_events' => $events->map(function($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location' => $event->location
            ];
        }),
        'upcoming_events' => $upcomingEvents->map(function($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location' => $event->location
            ];
        })
    ]);
});

Route::get('/debug-event-creation', function () {
    // Create a test event directly to verify everything works
    try {
        $event = \App\Models\Event::create([
            'name' => 'Debug Test Event',
            'location' => 'Test Location',
            'start_time' => '2025-10-19 10:00:00', // UTC time
            'end_time' => '2025-10-19 17:00:00',   // UTC time
            'max_capacity' => 50,
            'current_attendees' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test event created successfully',
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_time_utc' => $event->start_time,
                'end_time_utc' => $event->end_time,
                'start_time_ist' => $event->toTimezone('Asia/Kolkata')['start_time_local'],
                'end_time_ist' => $event->toTimezone('Asia/Kolkata')['end_time_local'],
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// In routes/web.php - Replace the old test-utc-conversion route
Route::get('/test-utc-conversion-fixed', function () {
    $testCases = [
        [
            'input' => '2025-10-18 10:00:00',
            'timezone' => 'Asia/Kolkata',
            'description' => '10:00 AM IST'
        ],
        [
            'input' => '2025-10-18 14:36:00', 
            'timezone' => 'Asia/Kolkata',
            'description' => '2:36 PM IST'
        ],
        [
            'input' => '2025-10-18 09:00:00',
            'timezone' => 'America/New_York', 
            'description' => '9:00 AM EDT'
        ]
    ];

    $results = [];

    foreach ($testCases as $test) {
        $inputCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $test['input'], $test['timezone']);
        $utcCarbon = $inputCarbon->copy()->setTimezone('UTC');
        $backToOriginal = $utcCarbon->copy()->setTimezone($test['timezone']);

        $results[] = [
            'test_case' => $test['description'],
            'input_time' => $inputCarbon->format('Y-m-d H:i:s P'),
            'converted_utc' => $utcCarbon->format('Y-m-d H:i:s P'),
            'retrieved_original' => $backToOriginal->format('Y-m-d H:i:s P'),
            'conversion_correct' => $inputCarbon->format('H:i:s') === $backToOriginal->format('H:i:s') ? '✅ YES' : '❌ NO',
            'timezone_offset' => $inputCarbon->format('P') . ' → ' . $utcCarbon->format('P')
        ];
    }

    return response()->json([
        'conversion_test' => $results,
        'timezone_info' => [
            'Asia/Kolkata' => 'UTC+5:30',
            'America/New_York' => 'UTC-4 (EDT)',
            'conversion_rule' => 'Input Time → UTC → Same Local Time'
        ]
    ]);
});
// In routes/web.php
Route::get('/test-conversion-with-helper', function () {
    $testCases = [
        ['2025-10-18 10:00:00', 'Asia/Kolkata'],
        ['2025-10-18 14:36:00', 'Asia/Kolkata'],
        ['2025-10-18 09:00:00', 'America/New_York'],
        ['2025-10-18 10:00:00', 'UTC'] // Test with UTC input
    ];

    $results = [];

    foreach ($testCases as $test) {
        list($datetime, $timezone) = $test;
        
        $result = \App\Helpers\TimezoneHelper::testConversionFlow($datetime, $timezone);
        $results[] = array_merge(['input' => $datetime, 'timezone' => $timezone], $result);
    }

    return response()->json([
        'conversion_tests' => $results,
        'summary' => [
            'total_tests' => count($results),
            'successful_conversions' => count(array_filter($results, fn($r) => $r['conversion_success'])),
            'failed_conversions' => count(array_filter($results, fn($r) => !$r['conversion_success']))
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