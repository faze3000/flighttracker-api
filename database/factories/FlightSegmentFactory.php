<?php

namespace Database\Factories;

use App\Enums\FlightSegmentStatus;
use App\Models\FlightSegment;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlightSegment>
 */
class FlightSegmentFactory extends Factory
{
    public function definition(): array
    {
        $trip = Trip::factory()->create();
        $departure = fake()->dateTimeBetween('-1 year', '+1 year');

        return [
            'trip_id' => $trip->id,
            'user_id' => $trip->user_id,
            'source_email_id' => null,
            'booking_reference' => strtoupper(fake()->bothify('??####')),
            'marketing_airline_iata' => 'BA',
            'operating_airline_iata' => 'BA',
            'marketing_flight_number' => (string) fake()->numberBetween(1, 999),
            'departure_airport_iata' => 'LHR',
            'departure_time_local' => $departure,
            'departure_timezone' => 'Europe/London',
            'arrival_airport_iata' => 'JFK',
            'arrival_time_local' => (clone $departure)->modify('+8 hours'),
            'arrival_timezone' => 'America/New_York',
            'cabin_class' => fake()->randomElement(['Economy', 'Premium Economy', 'Business', 'First']),
            'status' => FlightSegmentStatus::Confirmed,
            'confidence_score' => 0.95,
            'needs_review' => false,
        ];
    }
}
