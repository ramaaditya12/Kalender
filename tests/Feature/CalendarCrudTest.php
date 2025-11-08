<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if the calendar page loads and shows seeded events.
     */
    public function test_calendar_page_loads_with_events(): void
    {
        $event = Event::factory()->create([
            'title' => 'Test Event'
        ]);

        $response = $this->get('/calendar');

        $response->assertStatus(200);
        $response->assertSee('Test Event');
    }

    /**
     * Test creating a new event.
     */
    public function test_can_create_event(): void
    {
        $eventData = [
            'title' => 'Team Meeting',
            'start' => '2025-11-08T09:00:00',
            'end' => '2025-11-08T10:00:00',
            'color' => '#1d4ed8'
        ];

        $response = $this->postJson('/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Team Meeting',
                'start' => '2025-11-08T09:00:00',
                'end' => '2025-11-08T10:00:00',
                'color' => '#1d4ed8'
            ]);

        $this->assertDatabaseHas('events', $eventData);
    }

    /**
     * Test updating an existing event.
     */
    public function test_can_update_event(): void
    {
        $event = Event::factory()->create();

        $updatedData = [
            'title' => 'Updated Event',
            'start' => '2025-11-09T09:00:00',
            'end' => '2025-11-09T10:00:00',
            'color' => '#2563eb'
        ];

        $response = $this->putJson("/events/{$event->id}", $updatedData);

        $response->assertOk()
            ->assertJson($updatedData);

        $this->assertDatabaseHas('events', $updatedData);
    }

    /**
     * Test deleting an event.
     */
    public function test_can_delete_event(): void
    {
        $event = Event::factory()->create();

        $response = $this->deleteJson("/events/{$event->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test validation when creating an event.
     */
    public function test_event_requires_title_and_start_date(): void
    {
        $response = $this->postJson('/events', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start']);
    }

    /**
     * Test validation of end date being after start date.
     */
    public function test_end_date_must_be_after_start_date(): void
    {
        $response = $this->postJson('/events', [
            'title' => 'Invalid Event',
            'start' => '2025-11-08T10:00:00',
            'end' => '2025-11-08T09:00:00'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end']);
    }
}