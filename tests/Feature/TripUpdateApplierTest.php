<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\FlightSegment;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripUpdates\TripUpdateApplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TripUpdateApplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_a_gate_change_to_the_matching_segment(): void
    {
        $trip = Trip::factory()->for(User::factory())->create();
        $segment = FlightSegment::factory()->for($trip)->create([
            'user_id' => $trip->user_id,
            'marketing_airline_iata' => 'BA',
            'marketing_flight_number' => '117',
            'departure_airport_iata' => 'LHR',
            'departure_time_local' => '2026-09-01 10:00:00',
            'departure_gate' => 'A1',
        ]);

        (new TripUpdateApplier)->apply([
            'marketing_airline_iata' => 'BA',
            'marketing_flight_number' => '117',
            'departure_airport_iata' => 'LHR',
            'departure_date' => '2026-09-01',
            'changes' => ['departure_gate' => 'B12'],
        ]);

        $this->assertSame('B12', $segment->fresh()->departure_gate);
    }

    public function test_cancelling_every_segment_cancels_the_trip(): void
    {
        $trip = Trip::factory()->for(User::factory())->create(['status' => TripStatus::Upcoming]);
        $segment = FlightSegment::factory()->for($trip)->create([
            'user_id' => $trip->user_id,
            'marketing_airline_iata' => 'BA',
            'marketing_flight_number' => '117',
            'departure_airport_iata' => 'LHR',
            'departure_time_local' => '2026-09-01 10:00:00',
        ]);

        (new TripUpdateApplier)->apply([
            'marketing_airline_iata' => 'BA',
            'marketing_flight_number' => '117',
            'departure_airport_iata' => 'LHR',
            'departure_date' => '2026-09-01',
            'changes' => ['status' => 'cancelled'],
        ]);

        $this->assertSame(TripStatus::Cancelled, $trip->fresh()->status);
    }

    public function test_it_throws_when_no_segment_matches(): void
    {
        $this->expectException(RuntimeException::class);

        (new TripUpdateApplier)->apply([
            'marketing_airline_iata' => 'BA',
            'marketing_flight_number' => '999',
            'departure_airport_iata' => 'LHR',
            'departure_date' => '2026-09-01',
            'changes' => ['status' => 'cancelled'],
        ]);
    }
}
