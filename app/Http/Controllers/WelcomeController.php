<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/",
     *     summary="API Welcome",
     *     description="Welcome to Event Management API",
     *     operationId="welcome",
     *     tags={"General"},
     *     @OA\Response(
     *         response=200,
     *         description="Welcome message",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Welcome to Event Management API"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="api_version", type="string", example="1.0.0"),
     *                 @OA\Property(property="laravel_version", type="string", example="12.33.0"),
     *                 @OA\Property(property="php_version", type="string", example="8.2.29"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="endpoints", type="object",
     *                     @OA\Property(property="documentation", type="string", example="/swagger-live"),
     *                     @OA\Property(property="events", type="string", example="/api/events"),
     *                     @OA\Property(property="health", type="string", example="/up")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function welcome()
{
    if (request()->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Welcome to Event Management API',
            'data' => [
                'api_version' => '1.0.0',
                'documentation' => url('/swagger-live'),
                'frontend' => 'https://omnify-event-management-app.vercel.app'
            ]
        ]);
    }
    
    return view('welcome');
}

    /**
     * @OA\Get(
     *     path="/api",
     *     summary="API Base",
     *     description="API base endpoint with documentation links",
     *     operationId="apiBase",
     *     tags={"General"},
     *     @OA\Response(
     *         response=200,
     *         description="API information",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Event Management API is running"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="available_endpoints", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="method", type="string", example="GET"),
     *                         @OA\Property(property="path", type="string", example="/api/events"),
     *                         @OA\Property(property="description", type="string", example="List all events")
     *                     )
     *                 ),
     *                 @OA\Property(property="documentation", type="string", example="/swagger-live"),
     *                 @OA\Property(property="health_check", type="string", example="/up")
     *             )
     *         )
     *     )
     * )
     */
    public function apiBase(): JsonResponse
    {
        $endpoints = [
            [
                'method' => 'GET',
                'path' => '/api/events',
                'description' => 'List all events with filtering and pagination'
            ],
            [
                'method' => 'POST',
                'path' => '/api/events',
                'description' => 'Create a new event'
            ],
            [
                'method' => 'GET',
                'path' => '/api/events/locations',
                'description' => 'Get all unique event locations'
            ],
            [
                'method' => 'POST',
                'path' => '/api/events/{id}/register',
                'description' => 'Register an attendee for an event'
            ],
            [
                'method' => 'GET',
                'path' => '/api/events/{id}/attendees',
                'description' => 'Get all attendees for an event'
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Event Management API is running',
            'data' => [
                'available_endpoints' => $endpoints,
                'documentation' => url('/swagger-live'),
                'health_check' => url('/up'),
                'repository' => 'https://github.com/Thiru63/Omnify-Event-Management-App-Server',
                'frontend_app' => 'https://omnify-event-management-app.vercel.app'
            ]
        ]);
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            // Test database connection
            \DB::connection()->getPdo();
            
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'data' => [
                    'status' => 'operational',
                    'timestamp' => now()->toISOString(),
                    'database' => 'connected',
                    'environment' => app()->environment(),
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}