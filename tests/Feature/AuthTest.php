<?php

namespace Tests\Feature;

use App\Models\ForwardingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_forwarding_address(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue(ForwardingAddress::where('user_id', $user->id)->exists());
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada',
            'email' => 'ada2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'not-matching',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_a_user_can_log_out_and_the_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/auth/logout');

        $response->assertNoContent();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
