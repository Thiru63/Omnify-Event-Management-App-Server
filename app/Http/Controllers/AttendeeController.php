<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAttendeeRequest;
use App\Models\Attendee;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Attendees",
 *     description="Attendee management endpoints"
 * )
 */
class AttendeeController extends Controller
{
    use ApiResponse;

    /**
     * Register a new attendee for an event
     */

    /**
     * @OA\Post(
     *     path="/events/{event_id}/register",
     *     summary="Register an attendee for an event",
     *     description="Registers a new attendee for the specified event. Prevents overbooking and duplicate email registrations.",
     *     operationId="registerAttendee",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event_id",
     *         in="path",
     *         required=true,
     *         description="ID of the event to register for",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Attendee registration data",
     *         @OA\JsonContent(ref="#/components/schemas/RegisterAttendeeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attendee registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attendee registered successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Attendee")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Event not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or business rule violation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="This email is already registered for the event.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Event is full",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Event has reached maximum capacity")
     *         )
     *     )
     * )
     */
    public function register(RegisterAttendeeRequest $request, $eventId): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find the event
            $event = Event::find($eventId);
            
            if (!$event) {
                return $this->notFoundResponse('Event not found');
            }

            // Check event capacity
            if (!$event->hasAvailableCapacity()) {
                return $this->errorResponse(
                    'Event has reached maximum capacity',
                    Response::HTTP_CONFLICT
                );
            }

            // Create attendee (validation already passed)
            $attendee = Attendee::create([
                'event_id' => $eventId,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
            ]);

            // Update event attendee count
            $event->increment('current_attendees');

            DB::commit();

            return $this->createdResponse($attendee, 'Attendee registered successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e; // Let Laravel handle validation exceptions
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Attendee registration failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Failed to register attendee',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get all attendees for an event with pagination
     */

    /**
     * @OA\Get(
     *     path="/events/{event_id}/attendees",
     *     summary="Get all attendees for an event",
     *     description="Retrieves a paginated list of all attendees registered for the specified event.",
     *     operationId="getEventAttendees",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event_id",
     *         in="path",
     *         required=true,
     *         description="ID of the event",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of attendees per page (1-100, default: 15)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search_for",
     *         in="query",
     *         description="search_for term for attendee name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendees retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attendees retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Attendee")
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=45),
     *                     @OA\Property(property="last_page", type="integer", example=3),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=15)
     *                 ),
     *                 @OA\Property(property="event", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Tech Conference 2024"),
     *                     @OA\Property(property="current_attendees", type="integer", example=45),
     *                     @OA\Property(property="max_capacity", type="integer", example=100)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Event not found")
     *         )
     *     )
     * )
     */
    public function index(Request $request, $eventId): JsonResponse
    {
        try {
            // Find the event
            $event = Event::find($eventId);
            
            if (!$event) {
                return $this->notFoundResponse('Event not found');
            }

            // Get query parameters
            $perPage = $this->getValidatedPerPage($request->query('per_page', 15));
            $page = $this->getValidatedPage($request->query('page', 1));
            $search = $request->query('search_for');

            // Build query
            $query = Attendee::forEvent($eventId);

            // Apply search
            if ($search) {
                $query->search($search);
            }

            // Get paginated results
            $attendees = $query->orderBy('created_at', 'desc')
                             ->paginate($perPage, ['*'], 'page', $page);

            // Build response
            $responseData = [
                'data' => $attendees->items(),
                'pagination' => [
                    'current_page' => $attendees->currentPage(),
                    'per_page' => $attendees->perPage(),
                    'total' => $attendees->total(),
                    'last_page' => $attendees->lastPage(),
                    'from' => $attendees->firstItem(),
                    'to' => $attendees->lastItem(),
                ],
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'current_attendees' => $event->current_attendees,
                    'max_capacity' => $event->max_capacity,
                ]
            ];

            return $this->successResponse($responseData, 'Attendees retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Attendees retrieval failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Failed to retrieve attendees',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    // Helper methods (similar to EventController)
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
}