<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\FlightSegment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Airport::insert([
            ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city' => 'London', 'country' => 'United Kingdom', 'timezone' => 'Europe/London', 'lat' => 51.4700, 'lng' => -0.4543],
            ['iata_code' => 'JFK', 'name' => 'JFK', 'city' => 'New York', 'country' => 'United States', 'timezone' => 'America/New_York', 'lat' => 40.6413, 'lng' => -73.7781],
        ]);
    }

    public function test_lifetime_stats_aggregate_miles_countries_and_airlines(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        FlightSegment::factory()->for($trip)->create([
            'user_id' => $user->id,
            'departure_airport_iata' => 'LHR',
            'arrival_airport_iata' => 'JFK',
            'marketing_airline_iata' => 'BA',
            'cabin_class' => 'Business',
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/lifetime');

        $response->assertOk()
            ->assertJsonPath('data.total_flights', 1)
            ->assertJsonPath('data.countries_visited', 2)
            ->assertJsonPath('data.airlines_flown', 1);

        $this->assertGreaterThan(3000, $response->json('data.total_miles'));
    }

    public function test_cancelled_segments_are_excluded_from_stats(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        FlightSegment::factory()->for($trip)->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/lifetime');

        $response->assertOk()->assertJsonPath('data.total_flights', 0);
    }

    public function test_a_user_only_sees_their_own_stats(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $trip = Trip::factory()->for($other)->create();
        FlightSegment::factory()->for($trip)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->getJson('/api/stats/lifetime');

        $response->assertOk()->assertJsonPath('data.total_flights', 0);
    }
}
