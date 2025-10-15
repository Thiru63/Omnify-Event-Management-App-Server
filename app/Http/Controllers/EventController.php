<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEventRequest;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Info(
 *     title="Event Management API",
 *     version="1.0.0",
 *     description="API for managing events and attendee registrations",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 *
 * @OA\Tag(
 *     name="Events",
 *     description="Event management endpoints"
 * )
 */
class EventController extends Controller
{
    use ApiResponse;

    /*
     * Create a new event
     */

    /**
     * @OA\Post(
     *     path="/events",
     *     summary="Create a new event",
     *     description="Creates a new event with the provided details. All events are stored in IST timezone.",
     *     operationId="createEvent",
     *     tags={"Events"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Event creation data",
     *         @OA\JsonContent(
     *             required={"name", "location", "start_time", "end_time", "max_capacity"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Tech Conference 2024"),
     *             @OA\Property(property="location", type="string", maxLength=255, example="Bangalore Convention Center"),
     *             @OA\Property(
     *                 property="start_time", 
     *                 type="string", 
     *                 format="date-time", 
     *                 example="2024-12-20 10:00:00",
     *                 description="Start time in Y-m-d H:i:s format (IST timezone)"
     *             ),
     *             @OA\Property(
     *                 property="end_time", 
     *                 type="string", 
     *                 format="date-time", 
     *                 example="2024-12-20 17:00:00",
     *                 description="End time in Y-m-d H:i:s format (must be after start_time)"
     *             ),
     *             @OA\Property(
     *                 property="max_capacity", 
     *                 type="integer", 
     *                 minimum=1, 
     *                 example=100,
     *                 description="Maximum number of attendees allowed"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Event created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tech Conference 2024"),
     *                 @OA\Property(property="location", type="string", example="Bangalore Convention Center"),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-20T10:00:00.000000Z"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-20T17:00:00.000000Z"),
     *                 @OA\Property(property="max_capacity", type="integer", example=100),
     *                 @OA\Property(property="current_attendees", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", 
     *                     @OA\Items(type="string", example="The name field is required")
     *                 ),
     *                 @OA\Property(property="start_time", type="array",
     *                     @OA\Items(type="string", example="The start time must be a date after now")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create event"),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */

    public function store(CreateEventRequest $request): JsonResponse
    {
        try {  
            
            $event = Event::create([
                'name' => $request->validated('name'),
                'location' => $request->validated('location'),
                'start_time' => $request->validated('start_time'),
                'end_time' => $request->validated('end_time'),
                'max_capacity' => $request->validated('max_capacity'),
                'current_attendees' => 0,
            ]);

            return $this->createdResponse($event->fresh(), 'Event created successfully');

        } catch (\Exception $e) {
            \Log::error('Event creation failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Failed to create event',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /*
     * Get paginated list of events
     */

     /**
     * @OA\Get(
     *     path="/events",
     *     summary="Get paginated list of upcoming events",
     *     description="Retrieves a paginated list of upcoming events with advanced filtering, sorting, and search capabilities.",
     *     operationId="getEvents",
     *     tags={"Events"},
     *     @OA\Parameter(
     *         name="timezone",
     *         in="query",
     *         description="Timezone for event time display (default: Asia/Kolkata)",
     *         required=false,
     *         @OA\Schema(type="string", example="America/New_York")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of events per page (1-100, default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=2)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by (name, location, start_time, end_time, max_capacity, current_attendees)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "location", "start_time", "end_time", "max_capacity", "current_attendees"}, example="start_time")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="search_for",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string", example="conference")
     *     ),
     *     @OA\Parameter(
     *         name="search_in",
     *         in="query",
     *         description="Fields to search in (comma-separated or 'all')",
     *         required=false,
     *         @OA\Schema(type="string", example="name,location")
     *     ),
     *     @OA\Parameter(
     *         name="filter_by_location",
     *         in="query",
     *         description="Filter by location(s) - comma separated",
     *         required=false,
     *         @OA\Schema(type="string", example="Bangalore,Delhi")
     *     ),
     *     @OA\Parameter(
     *         name="seat_available_events",
     *         in="query",
     *         description="Filter events with available seats",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Events retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Events retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Tech Conference 2024"),
     *                         @OA\Property(property="location", type="string", example="Bangalore"),
     *                         @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-20T10:00:00+05:30"),
     *                         @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-20T17:00:00+05:30"),
     *                         @OA\Property(property="max_capacity", type="integer", example=100),
     *                         @OA\Property(property="current_attendees", type="integer", example=25),
     *                         @OA\Property(property="available_capacity", type="integer", example=75)
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=10),
     *                     @OA\Property(property="total", type="integer", example=50),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=10)
     *                 ),
     *                 @OA\Property(property="filters_applied", type="object",
     *                     @OA\Property(property="sort_by", type="string", example="start_time"),
     *                     @OA\Property(property="sort_order", type="string", example="asc"),
     *                     @OA\Property(property="search_for", type="string", example=null),
     *                     @OA\Property(property="search_in", type="string", example="name"),
     *                     @OA\Property(property="filter_by_location", type="array", @OA\Items(type="string"), example=null),
     *                     @OA\Property(property="seat_available_events", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid timezone or parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid timezone provided"),
     *             @OA\Property(property="error", type="string", example="Timezone must be a valid PHP timezone identifier")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve events"),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            // Get query parameters with validation
            $timezone = $request->query('timezone', 'Asia/Kolkata');
            $perPage = $this->getValidatedPerPage($request->query('per_page', 10));
            $page = $this->getValidatedPage($request->query('page', 1));
            
            // Advanced query parameters
            $sortBy = $request->query('sort_by', 'start_time');
            $sortOrder = $request->query('sort_order', 'asc');
            $searchFor = $request->query('search_for');
            $searchIn = $request->query('search_in', 'name');
            $filterLocation = $request->query('filter_by_location');
            $seatAvailable = $request->query('seat_available_events');
            
            // Validate timezone
            if (!$this->isValidTimezone($timezone)) {
                return $this->errorResponse(
                    'Invalid timezone provided', 
                    Response::HTTP_UNPROCESSABLE_ENTITY, 
                    'Timezone must be a valid PHP timezone identifier'
                );
            }

            // Start building query
            $query = Event::upcoming();

            // Apply location filter
            if ($filterLocation) {
                $locations = is_array($filterLocation) ? $filterLocation : explode(',', $filterLocation);
                $query->locationIn(array_map('trim', $locations));
            }

            // Apply seat availability filter
            if ($seatAvailable === 'true' || $seatAvailable === '1') {
                $query->withAvailableSeats();
            }

            // Apply search using the model scope (BEST METHOD)
            if ($searchFor) {
                $searchFields = $this->getSearchFields($searchIn);
                $query->search($searchFor, $searchFields);
            }

            // Apply sorting
            $validSortFields = ['name', 'location', 'start_time', 'end_time', 'max_capacity', 'current_attendees'];
            $validSortOrders = ['asc', 'desc'];

            if (in_array($sortBy, $validSortFields) && in_array(strtolower($sortOrder), $validSortOrders)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('start_time', 'asc');
            }

            // Execute query with pagination (CLEANER VERSION - your preference)
            $events = $query->paginate(perPage: $perPage, page: $page);

            // Transform events to specified timezone
            $transformedEvents = $events->through(function ($event) use ($timezone) {
                return $event->toTimezone($timezone);
            });

            // Build response using trait (consistent structure)
            $responseData = [
                'data' => $transformedEvents->items(),
                'pagination' => [
                    'current_page' => $transformedEvents->currentPage(),
                    'per_page' => $transformedEvents->perPage(),
                    'total' => $transformedEvents->total(),
                    'last_page' => $transformedEvents->lastPage(),
                    'from' => $transformedEvents->firstItem(),
                    'to' => $transformedEvents->lastItem(),
                ],
                'filters_applied' => [
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                    'search_for' => $searchFor,
                    'search_in' => $searchIn,
                    'filter_by_location' => $filterLocation ? (is_array($filterLocation) ? $filterLocation : explode(',', $filterLocation)) : null,
                    'seat_available_events' => (bool)$seatAvailable,
                ]
            ];

            return $this->successResponse($responseData, 'Events retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Events retrieval failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Failed to retrieve events',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /*
     * Get all unique locations
     */

    /**
     * @OA\Get(
     *     path="/events/locations",
     *     summary="Get all unique event locations",
     *     description="Retrieves a list of all unique locations from upcoming events",
     *     operationId="getEventLocations",
     *     tags={"Events"},
     *     @OA\Response(
     *         response=200,
     *         description="Locations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Locations retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="string", example="Bangalore Convention Center")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve locations"),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */

    public function getLocations(): JsonResponse
    {
        try {
            $locations = Event::upcoming()
                ->distinct()
                ->orderBy('location')
                ->pluck('location')
                ->filter()
                ->values();

            return $this->successResponse($locations, 'Locations retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Locations retrieval failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Failed to retrieve locations',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    // Helper methods
    private function getValidatedPerPage($perPage): int
    {
        $perPage = (int) $perPage;
        return min(max($perPage, 1), 100);
    }

    private function getValidatedPage($page): int
    {
        $page = (int) $page;
        return max($page, 1);
    }

    private function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, \DateTimeZone::listIdentifiers());
    }

    private function getSearchFields(string $searchIn): array
    {
        $allSearchableFields = ['name', 'location', 'start_time', 'end_time', 'max_capacity', 'current_attendees'];
        
        if ($searchIn === 'all') {
            return $allSearchableFields;
        }

        $requestedFields = explode(',', $searchIn);
        return array_intersect($requestedFields, $allSearchableFields);
    }
}