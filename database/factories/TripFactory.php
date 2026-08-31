<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', '+1 year');

        return [
            'user_id' => User::factory(),
            'label' => fake()->city().' trip',
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+'.fake()->numberBetween(1, 10).' days'),
            'status' => fake()->randomElement(TripStatus::cases()),
        ];
    }
}
