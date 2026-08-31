<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_a_trip(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/trips', [
            'label' => 'Japan',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
        ]);

        $response->assertCreated()->assertJsonPath('data.label', 'Japan');
        $this->assertDatabaseHas('trips', ['user_id' => $user->id, 'label' => 'Japan']);
    }

    public function test_a_user_only_sees_their_own_trips(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Trip::factory()->for($user)->create();
        Trip::factory()->for($other)->create();

        $response = $this->actingAs($user)->getJson('/api/trips');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_user_cannot_view_another_users_trip(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $trip = Trip::factory()->for($owner)->create();

        $this->actingAs($intruder)->getJson("/api/trips/{$trip->id}")->assertForbidden();
    }

    public function test_a_user_cannot_update_another_users_trip(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $trip = Trip::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->patchJson("/api/trips/{$trip->id}", ['label' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('trips', ['id' => $trip->id, 'label' => 'Hijacked']);
    }

    public function test_a_user_cannot_delete_another_users_trip(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $trip = Trip::factory()->for($owner)->create();

        $this->actingAs($intruder)->deleteJson("/api/trips/{$trip->id}")->assertForbidden();
        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
    }

    public function test_the_owner_can_update_and_delete_their_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/trips/{$trip->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($user)->deleteJson("/api/trips/{$trip->id}")->assertNoContent();
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }
}
