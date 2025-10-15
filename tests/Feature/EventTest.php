<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Event;
use Carbon\Carbon;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force clean events table before each test
        \DB::statement('TRUNCATE events RESTART IDENTITY CASCADE');
    }

    /**
     * Extract data from API response (handles nested structure)
     */
    private function getResponseData($response): array
    {
        $responseData = $response->json();
        
        if (isset($responseData['data']['data'])) {
            // Nested structure: {data: {data: [], pagination: {}}}
            return $responseData['data']['data'];
        } elseif (isset($responseData['data'])) {
            // Standard structure: {data: [], pagination: {}}
            return $responseData['data'];
        } else {
            // Raw structure: []
            return $responseData;
        }
    }

    /**
     * Test successful event creation
     */
    public function test_can_create_event_with_valid_data(): void
    {
        $eventData = [
            'name' => 'PHP Conference 2024',
            'location' => 'New Delhi',
            'start_time' => Carbon::now()->addDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(10)->addHours(8)->format('Y-m-d H:i:s'),
            'max_capacity' => 200
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Event created successfully'
                ]);

        $this->assertDatabaseHas('events', [
            'name' => 'PHP Conference 2024',
            'location' => 'New Delhi',
            'max_capacity' => 200,
            'current_attendees' => 0
        ]);
    }

    /**
     * Test sorting events by different fields
     */
    public function test_can_sort_events_by_different_fields(): void
    {
        Event::factory()->create(['name' => 'Conference A', 'start_time' => Carbon::now()->addDays(20)]);
        Event::factory()->create(['name' => 'Conference B', 'start_time' => Carbon::now()->addDays(10)]);

        $response = $this->getJson('/api/events?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $this->getResponseData($response);
        $this->assertCount(2, $data);
        $this->assertEquals('Conference A', $data[0]['name']);
        $this->assertEquals('Conference B', $data[1]['name']);

        // Sort by start_time descending
        $response = $this->getJson('/api/events?sort_by=start_time&sort_order=desc');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(2, $data);
        $this->assertEquals('Conference A', $data[0]['name']);
    }

    /**
     * Test searching events
     */
    public function test_can_search_events(): void
    {
        Event::factory()->create(['name' => 'Tech Conference', 'location' => 'Bangalore']);
        Event::factory()->create(['name' => 'Music Festival', 'location' => 'Mumbai']);
        Event::factory()->create(['name' => 'Art Exhibition', 'location' => 'Delhi']);

        // Search by name
        $response = $this->getJson('/api/events?search_for=Tech&search_in=name');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(1, $data);
        $this->assertEquals('Tech Conference', $data[0]['name']);

        // Search by location
        $response = $this->getJson('/api/events?search_for=Mumbai&search_in=location');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(1, $data);
        $this->assertEquals('Mumbai', $data[0]['location']);
    }

    /**
     * Test filtering events by location
     */
    public function test_can_filter_events_by_location(): void
    {
        Event::factory()->create(['location' => 'Bangalore']);
        Event::factory()->create(['location' => 'Mumbai']);
        Event::factory()->create(['location' => 'Delhi']);

        // Single location filter
        $response = $this->getJson('/api/events?filter_by_location=Bangalore');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(1, $data);
        $this->assertEquals('Bangalore', $data[0]['location']);

        // Multiple locations filter
        $response = $this->getJson('/api/events?filter_by_location=Bangalore,Mumbai');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(2, $data);
        $locations = collect($data)->pluck('location')->toArray();
        $this->assertContains('Bangalore', $locations);
        $this->assertContains('Mumbai', $locations);
    }

    /**
     * Test filtering events with available seats
     */
    public function test_can_filter_events_with_available_seats(): void
    {
        Event::factory()->create(['max_capacity' => 100, 'current_attendees' => 50]); // Available
        Event::factory()->create(['max_capacity' => 100, 'current_attendees' => 100]); // Full
        Event::factory()->create(['max_capacity' => 200, 'current_attendees' => 150]); // Available

        $response = $this->getJson('/api/events?seat_available_events=true');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        
        $this->assertCount(2, $data);
        
        foreach ($data as $event) {
            $this->assertTrue($event['available_capacity'] > 0);
        }
    }

    /**
     * Test getting unique locations
     */
    public function test_can_get_unique_locations(): void
    {
        Event::factory()->create(['location' => 'Bangalore']);
        Event::factory()->create(['location' => 'Mumbai']);
        Event::factory()->create(['location' => 'Bangalore']); // Duplicate

        $response = $this->getJson('/api/events/locations');
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Locations retrieved successfully'
                ]);

        $responseData = $response->json();
        $data = $this->getResponseData($response);
        $this->assertCount(2, $data);
        $this->assertContains('Bangalore', $data);
        $this->assertContains('Mumbai', $data);
    }

    /**
     * Test combined queries (pagination + sort + filter + search)
     */
    public function test_combined_queries_work_together(): void
    {
        Event::factory()->create(['name' => 'Tech A', 'location' => 'Bangalore', 'max_capacity' => 100, 'current_attendees' => 50]);
        Event::factory()->create(['name' => 'Tech B', 'location' => 'Mumbai', 'max_capacity' => 100, 'current_attendees' => 100]);
        Event::factory()->create(['name' => 'Music A', 'location' => 'Bangalore', 'max_capacity' => 200, 'current_attendees' => 50]);

        $response = $this->getJson('/api/events?search_for=Tech&search_in=name&filter_by_location=Bangalore&sort_by=name&sort_order=asc&per_page=5&page=1');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Events retrieved successfully'
                ]);

        $data = $this->getResponseData($response);
        $this->assertCount(1, $data);
        $this->assertEquals('Tech A', $data[0]['name']);
        $this->assertEquals('Bangalore', $data[0]['location']);
    }

    /**
     * Test search with different field types
     */
    public function test_search_works_with_different_field_types(): void
    {
        Event::factory()->create(['name' => 'Test Event', 'max_capacity' => 150]);
        Event::factory()->create(['name' => 'Another Event', 'max_capacity' => 200]);

        // Search numeric field
        $response = $this->getJson('/api/events?search_for=150&search_in=max_capacity');
        $response->assertStatus(200);
        $data = $this->getResponseData($response);
        $this->assertCount(1, $data);
        $this->assertEquals(150, $data[0]['max_capacity']);

        // Search date field
        $futureDate = Carbon::now()->addDays(15)->format('Y-m-d');
        $response = $this->getJson("/api/events?search_for={$futureDate}&search_in=start_time");
        $response->assertStatus(200);
    }

    /**
     * Test invalid sort parameters use defaults
     */
    public function test_invalid_sort_parameters_use_defaults(): void
    {
        Event::factory()->create(['name' => 'Event A', 'start_time' => Carbon::now()->addDays(5)]);

        $response = $this->getJson('/api/events?sort_by=invalid_field&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $this->getResponseData($response);
        $this->assertNotEmpty($data);
    }


    /*
     * Test event creation validation - past date
     */
    public function test_cannot_create_event_with_past_start_time(): void
    {
        $eventData = [
            'name' => 'Past Event',
            'location' => 'Mumbai',
            'start_time' => '2020-01-01 10:00:00',
            'end_time' => '2020-01-01 18:00:00',
            'max_capacity' => 100
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_time']);
    }

    /*
     * Test event creation validation - end time before start time
     */
    public function test_cannot_create_event_with_end_time_before_start_time(): void
    {
        $eventData = [
            'name' => 'Invalid Time Event',
            'location' => 'Chennai',
            'start_time' => Carbon::now()->addDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'), // End before start
            'max_capacity' => 100
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_time']);
    }

    /*
     * Test event creation validation - zero capacity
     */
    public function test_cannot_create_event_with_zero_capacity(): void
    {
        $eventData = [
            'name' => 'Zero Capacity Event',
            'location' => 'Kolkata',
            'start_time' => Carbon::now()->addDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(10)->addHours(8)->format('Y-m-d H:i:s'),
            'max_capacity' => 0
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['max_capacity']);
    }

    /*
     * Test event creation validation - missing required fields
     */
    public function test_cannot_create_event_with_missing_required_fields(): void
    {
        $eventData = [
            'name' => 'Incomplete Event'
            // Missing other required fields
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['location', 'start_time', 'end_time', 'max_capacity']);
    }
}