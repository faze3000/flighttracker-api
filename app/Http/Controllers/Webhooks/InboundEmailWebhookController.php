<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\RawEmailSource;
use App\Http\Controllers\Controller;
use App\Models\ForwardingAddress;
use App\Services\Email\InboundEmailSignatureVerifier;
use App\Services\Email\RawEmailIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundEmailWebhookController extends Controller
{
    public function __construct(
        private readonly InboundEmailSignatureVerifier $verifier,
        private readonly RawEmailIngestor $ingestor,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request)) {
            abort(401, 'Invalid signature.');
        }

        $data = $request->validate([
            'to' => ['required', 'email'],
            'message_id' => ['nullable', 'string'],
            'raw_mime' => ['required', 'string'],
        ]);

        $forwardingAddress = ForwardingAddress::where('address', $data['to'])->first();

        if ($forwardingAddress === null) {
            abort(404, 'Unknown forwarding address.');
        }

        $this->ingestor->ingest(
            user: $forwardingAddress->user,
            source: RawEmailSource::Forwarded,
            externalMessageId: $data['message_id'] ?? null,
            rawContent: $data['raw_mime'],
            receivedAt: now(),
        );

        return response()->json(status: 202);
    }
}
