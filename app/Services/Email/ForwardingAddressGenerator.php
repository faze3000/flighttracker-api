<?php

namespace App\Services\Email;

use App\Models\ForwardingAddress;
use App\Models\User;
use Illuminate\Support\Str;

class ForwardingAddressGenerator
{
    /**
     * Generate and persist a unique forwarding address for a user.
     * Safe to call once per user (registration); returns the existing
     * address instead of generating a second one if it already exists.
     */
    public function generateFor(User $user): ForwardingAddress
    {
        if ($user->forwardingAddress !== null) {
            return $user->forwardingAddress;
        }

        $domain = config('flighttracker.forwarding_domain');

        do {
            $localPart = Str::lower(Str::random(10));
            $address = "{$localPart}@{$domain}";
        } while (ForwardingAddress::where('address', $address)->exists());

        return ForwardingAddress::create([
            'user_id' => $user->id,
            'address' => $address,
        ]);
    }
}
