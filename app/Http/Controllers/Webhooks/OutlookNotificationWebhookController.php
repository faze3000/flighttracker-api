<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOutlookChangeNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OutlookNotificationWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Subscription-creation handshake: Graph GETs/POSTs the endpoint with
        // ?validationToken=... and expects it echoed back as plain text,
        // unauthenticated, within 10 seconds.
        if ($request->has('validationToken')) {
            return response($request->query('validationToken'), 200)
                ->header('Content-Type', 'text/plain');
        }

        $expectedClientState = config('services.outlook.webhook_client_state');

        foreach ($request->input('value', []) as $notification) {
            if (! hash_equals((string) $expectedClientState, (string) ($notification['clientState'] ?? ''))) {
                continue;
            }

            $subscriptionId = $notification['subscriptionId'] ?? null;
            $messageId = $notification['resourceData']['id'] ?? null;

            if ($subscriptionId !== null && $messageId !== null) {
                ProcessOutlookChangeNotification::dispatch($subscriptionId, $messageId);
            }
        }

        return response('', SymfonyResponse::HTTP_ACCEPTED);
    }
}
