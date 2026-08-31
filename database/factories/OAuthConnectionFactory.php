<?php

namespace Database\Factories;

use App\Enums\OAuthConnectionStatus;
use App\Enums\OAuthProvider;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OAuthConnection>
 */
class OAuthConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => OAuthProvider::Gmail,
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'provider_account_email' => fake()->safeEmail(),
            'status' => OAuthConnectionStatus::Active,
        ];
    }
}
