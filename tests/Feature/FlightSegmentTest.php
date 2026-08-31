<?php

namespace Tests\Feature;

use App\Models\FlightSegment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSegmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_update_a_segment_and_it_resolves_needs_review(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        $segment = FlightSegment::factory()->for($trip)->create([
            'user_id' => $user->id,
            'needs_review' => true,
            'seat' => null,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/segments/{$segment->id}", [
            'seat' => '12A',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.seat', '12A')
            ->assertJsonPath('data.needs_review', false);

        $this->assertDatabaseHas('flight_segments', ['id' => $segment->id, 'seat' => '12A', 'needs_review' => false]);
    }

    public function test_a_user_cannot_update_another_users_segment(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $trip = Trip::factory()->for($owner)->create();
        $segment = FlightSegment::factory()->for($trip)->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->patchJson("/api/segments/{$segment->id}", ['seat' => '1A'])
            ->assertForbidden();
    }
}
