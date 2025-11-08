<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeBetween('-1 month', '+1 month'));
        $end = $this->faker->boolean(80) ? $start->copy()->addHours(rand(1, 4)) : null;
        
        return [
            'title' => $this->faker->sentence(3),
            'start' => $start,
            'end' => $end,
            'color' => $this->faker->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444']),
        ];
    }
}