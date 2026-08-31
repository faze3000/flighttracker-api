<?php

namespace Database\Factories;

use App\Enums\RawEmailSource;
use App\Enums\RawEmailStatus;
use App\Models\RawEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RawEmail>
 */
class RawEmailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => RawEmailSource::Forwarded,
            'external_message_id' => fake()->unique()->uuid(),
            'status' => RawEmailStatus::Pending,
            'raw_content' => "Subject: Booking confirmation\n\nYour flight is confirmed.",
            'received_at' => now(),
        ];
    }
}
