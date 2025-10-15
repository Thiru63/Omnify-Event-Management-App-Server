<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Event;
use App\Models\Attendee;
use Carbon\Carbon;

class AttendeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force clean tables before each test
        \DB::statement('TRUNCATE events RESTART IDENTITY CASCADE');
        \DB::statement('TRUNCATE attendees RESTART IDENTITY CASCADE');
    }

    /**
     * Test successful attendee registration
     */
    public function test_can_register_attendee_for_event(): void
    {
        $event = Event::factory()->create([
            'max_capacity' => 100,
            'current_attendees' => 0
        ]);

        $attendeeData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $response = $this->postJson("/api/events/{$event->id}/register", $attendeeData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Attendee registered successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'event_id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ]
                ]);

        $this->assertDatabaseHas('attendees', [
            'event_id' => $event->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Check that event attendee count was updated
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'current_attendees' => 1
        ]);
    }

    /**
     * Test duplicate email registration prevention
     */
    public function test_cannot_register_duplicate_email_for_same_event(): void
    {
        $event = Event::factory()->create([
            'max_capacity' => 100,
            'current_attendees' => 1
        ]);

        // Create first attendee
        Attendee::create([
            'event_id' => $event->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Try to register same email again
        $attendeeData = [
            'name' => 'John Smith',
            'email' => 'john@example.com' // Same email
        ];

        $response = $this->postJson("/api/events/{$event->id}/register", $attendeeData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email'])
                ->assertJson([
                    'errors' => [
                        'email' => ['This email is already registered for the event.']
                    ]
                ]);

        // Ensure no new attendee was created
        $this->assertDatabaseCount('attendees', 1);
        $this->assertDatabaseHas('attendees', [
            'event_id' => $event->id,
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test overbooking prevention
     */
    public function test_cannot_register_attendee_when_event_full(): void
    {
        $event = Event::factory()->create([
            'max_capacity' => 2,
            'current_attendees' => 2 // Already at capacity
        ]);

        $attendeeData = [
            'name' => 'New Person',
            'email' => 'new@example.com'
        ];

        $response = $this->postJson("/api/events/{$event->id}/register", $attendeeData);

        $response->assertStatus(409) // HTTP_CONFLICT
                ->assertJson([
                    'success' => false,
                    'message' => 'Event has reached maximum capacity'
                ]);

        // Ensure no new attendee was created
        $this->assertDatabaseCount('attendees', 0);
    }

    /**
     * Test registering for non-existent event
     */
    public function test_cannot_register_for_nonexistent_event(): void
    {
        $attendeeData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $response = $this->postJson("/api/events/999/register", $attendeeData);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Event not found'
                ]);
    }

    /**
     * Test validation errors for attendee registration
     */
    public function test_validation_errors_for_attendee_registration(): void
    {
        $event = Event::factory()->create();

        // Test missing required fields
        $response = $this->postJson("/api/events/{$event->id}/register", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);

        // Test invalid email format
        $response = $this->postJson("/api/events/{$event->id}/register", [
            'name' => 'John Doe',
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test getting attendees for an event
     */
    public function test_can_get_attendees_for_event(): void
    {
        $event = Event::factory()->create();
        
        // Create multiple attendees
        Attendee::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/events/{$event->id}/attendees");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Attendees retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'event_id',
                                'name',
                                'email',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'last_page',
                            'from',
                            'to'
                        ],
                        'event' => [
                            'id',
                            'name',
                            'current_attendees',
                            'max_capacity'
                        ]
                    ]
                ]);

        $responseData = $response->json();
        $this->assertCount(3, $responseData['data']['data']);
        $this->assertEquals(3, $responseData['data']['pagination']['total']);
    }

    /**
     * Test getting attendees for non-existent event
     */
    public function test_cannot_get_attendees_for_nonexistent_event(): void
    {
        $response = $this->getJson("/api/events/999/attendees");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Event not found'
                ]);
    }

    /**
     * Test pagination for attendees list
     */
    public function test_attendees_list_pagination_works(): void
    {
        $event = Event::factory()->create();
        
        // Create 5 attendees
        Attendee::factory()->count(5)->create(['event_id' => $event->id]);

        // Request first page with 2 items
        $response = $this->getJson("/api/events/{$event->id}/attendees?per_page=2&page=1");

        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(2, $responseData['data']['data']);
        $this->assertEquals(1, $responseData['data']['pagination']['current_page']);
        $this->assertEquals(2, $responseData['data']['pagination']['per_page']);
        $this->assertEquals(5, $responseData['data']['pagination']['total']);
        $this->assertEquals(3, $responseData['data']['pagination']['last_page']);
    }

    /**
     * Test searching attendees with search_for parameter
     */
    public function test_can_search_attendees_by_name_or_email(): void
    {
        $event = Event::factory()->create();
        
        // Create attendees with different names and emails
        Attendee::factory()->create([
            'event_id' => $event->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        Attendee::factory()->create([
            'event_id' => $event->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);
        
        Attendee::factory()->create([
            'event_id' => $event->id,
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com'
        ]);

        // Search by name
        $response = $this->getJson("/api/events/{$event->id}/attendees?search_for=John");

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(2, $responseData['data']['data']); // John Doe and Bob Johnson
        $names = collect($responseData['data']['data'])->pluck('name')->toArray();
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);

        // Search by email
        $response = $this->getJson("/api/events/{$event->id}/attendees?search_for=jane@example.com");

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['data']);
        $this->assertEquals('Jane Smith', $responseData['data']['data'][0]['name']);
    }

    /**
     * Test empty search returns all attendees
     */
    public function test_empty_search_returns_all_attendees(): void
    {
        $event = Event::factory()->create();
        Attendee::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/events/{$event->id}/attendees?search_for=");

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(3, $responseData['data']['data']);
    }

    /**
     * Test case-insensitive search
     */
    public function test_search_is_case_insensitive(): void
    {
        $event = Event::factory()->create();
        
        Attendee::factory()->create([
            'event_id' => $event->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Search with different case
        $response = $this->getJson("/api/events/{$event->id}/attendees?search_for=JOHN");

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['data']);
        $this->assertEquals('John Doe', $responseData['data']['data'][0]['name']);
    }

    /**
     * Test rate limiting for registration endpoint
     */
    public function test_rate_limiting_for_registration_endpoint(): void
    {
        $event = Event::factory()->create(['max_capacity' => 100]);

        // Make 6 rapid requests (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson("/api/events/{$event->id}/register", [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]);
        }

        // Last request should be rate limited
        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again later.'
                ]);
    }

    /**
     * Test that same email can register for different events
     */
    /**
 * Test that same email can register for different events
 */
public function test_same_email_can_register_for_different_events(): void
{
    $event1 = Event::factory()->create([
        'max_capacity' => 100,
        'current_attendees' => 0
    ]);
    
    $event2 = Event::factory()->create([
        'max_capacity' => 100, 
        'current_attendees' => 0
    ]);

    $attendeeData = [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ];

    // Register for first event
    $response1 = $this->postJson("/api/events/{$event1->id}/register", $attendeeData);
    $response1->assertStatus(201);

    // Register same email for second event
    $response2 = $this->postJson("/api/events/{$event2->id}/register", $attendeeData);
    $response2->assertStatus(201);

    // Both should be in database
    $this->assertDatabaseHas('attendees', [
        'event_id' => $event1->id,
        'email' => 'john@example.com'
    ]);
    
    $this->assertDatabaseHas('attendees', [
        'event_id' => $event2->id,
        'email' => 'john@example.com'
    ]);
}

    /**
     * Test attendee registration updates event capacity correctly
     */
    public function test_attendee_registration_updates_event_capacity(): void
    {
        $event = Event::factory()->create([
            'max_capacity' => 3,
            'current_attendees' => 0
        ]);

        // Register 3 attendees
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson("/api/events/{$event->id}/register", [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]);
            $response->assertStatus(201);

            // Check event capacity after each registration
            $event->refresh();
            $this->assertEquals($i, $event->current_attendees);
        }

        // Fourth registration should fail
        $response = $this->postJson("/api/events/{$event->id}/register", [
            'name' => "User 4",
            'email' => "user4@example.com"
        ]);
        $response->assertStatus(409);

        // Capacity should remain at 3
        $event->refresh();
        $this->assertEquals(3, $event->current_attendees);
    }
}