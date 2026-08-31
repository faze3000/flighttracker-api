<?php

namespace Tests\Feature;

use App\Models\Lounge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoungeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lounges_can_be_listed_by_airport(): void
    {
        $user = User::factory()->create();
        Lounge::create(['airport_iata' => 'LHR', 'name' => 'BA Galleries Club', 'terminal' => '5']);
        Lounge::create(['airport_iata' => 'JFK', 'name' => 'Delta One Lounge']);

        $response = $this->actingAs($user)->getJson('/api/lounges?airport=LHR');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'BA Galleries Club');
    }

    public function test_airport_query_parameter_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/lounges')->assertUnprocessable();
    }
}
