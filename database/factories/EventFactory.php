<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/*
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /*
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Always create events in the future
        $startDate = Carbon::now()->addDays($this->faker->numberBetween(1, 30));
        
        return [
            'name' => $this->faker->sentence(3),
            'location' => $this->faker->city(),
            'start_time' => $startDate,
            'end_time' => $startDate->copy()->addHours($this->faker->numberBetween(2, 8)),
            'max_capacity' => $this->faker->numberBetween(50, 500),
            'current_attendees' => $this->faker->numberBetween(0, 100),
        ];
    }

    /*
     * Indicate that the event is in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'end_time' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /*
     * Indicate that the event has specific capacity.
     */
    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'max_capacity' => $capacity,
        ]);
    }

    /*
     * Indicate that the event has specific attendee count.
     */
    public function withAttendees(int $attendees): static
    {
        return $this->state(fn (array $attributes) => [
            'current_attendees' => $attendees,
        ]);
    }
}