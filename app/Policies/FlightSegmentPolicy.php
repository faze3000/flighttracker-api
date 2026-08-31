<?php

namespace App\Policies;

use App\Models\FlightSegment;
use App\Models\User;

class FlightSegmentPolicy
{
    public function update(User $user, FlightSegment $segment): bool
    {
        return $user->id === $segment->user_id;
    }
}
