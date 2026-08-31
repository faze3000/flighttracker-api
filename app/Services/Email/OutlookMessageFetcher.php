<?php

namespace App\Services\Email;

use App\Models\OAuthConnection;
use App\Services\OAuth\OAuthConnectionService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A Microsoft Graph change notification carries the changed message's id
 * directly (unlike Gmail's history-cursor model), so fetching it is a
 * single call — we still fetch fresh from Graph rather than trusting the
 * notification body, per Microsoft's guidance.
 */
class OutlookMessageFetcher
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    public function __construct(private readonly OAuthConnectionService $connections) {}

    /**
     * @return array{external_message_id: string, raw_content: string}
     */
    public function fetchMessage(OAuthConnection $connection, string $messageId): array
    {
        $connection = $this->connections->ensureFreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->get(self::GRAPH_BASE."/me/messages/{$messageId}/\$value");

        if ($response->failed()) {
            throw new RuntimeException("Fetching Outlook message {$messageId} failed: ".$response->body());
        }

        return [
            'external_message_id' => $messageId,
            'raw_content' => $response->body(),
        ];
    }
}
