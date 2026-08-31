<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\OAuthConnectionStatus;
use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessGmailPushNotification;
use App\Models\OAuthConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GmailPushWebhookController extends Controller
{
    /**
     * Google Pub/Sub push receiver. Verified by a token embedded in the
     * subscription's own endpoint URL (?token=...) rather than a signed
     * payload — the pattern Google's docs recommend for push endpoints
     * that can't otherwise validate the OIDC token Pub/Sub can attach.
     */
    public function handle(Request $request): JsonResponse
    {
        $expected = config('services.gmail.push_verification_token');

        if (empty($expected) || ! hash_equals($expected, (string) $request->query('token'))) {
            abort(401, 'Invalid verification token.');
        }

        $data = json_decode(base64_decode($request->input('message.data', '')), associative: true) ?? [];

        $emailAddress = $data['emailAddress'] ?? null;
        $historyId = $data['historyId'] ?? null;

        if ($emailAddress === null || $historyId === null) {
            // Ack anyway — Pub/Sub will keep redelivering a malformed
            // message forever otherwise, and there's nothing to retry into.
            return response()->json(status: 200);
        }

        $connection = OAuthConnection::query()
            ->where('provider', OAuthProvider::Gmail)
            ->where('provider_account_email', $emailAddress)
            ->where('status', OAuthConnectionStatus::Active)
            ->first();

        if ($connection !== null) {
            ProcessGmailPushNotification::dispatch($connection->id, (string) $historyId);
        }

        return response()->json(status: 200);
    }
}
