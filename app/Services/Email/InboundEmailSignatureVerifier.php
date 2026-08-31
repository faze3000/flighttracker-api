<?php

namespace App\Services\Email;

use Illuminate\Http\Request;

class InboundEmailSignatureVerifier
{
    public function verify(Request $request): bool
    {
        $secret = config('services.inbound_email.webhook_secret');
        $signature = $request->header('X-Webhook-Signature');

        if (empty($secret) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
