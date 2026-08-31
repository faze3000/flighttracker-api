<?php

namespace App\Services\Email;

use App\Models\OAuthConnection;
use App\Services\OAuth\OAuthConnectionService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Turns a Gmail Pub/Sub push notification (mailbox + new historyId) into
 * the raw content of whatever messages actually arrived since our last
 * synced historyId, via the Gmail API.
 */
class GmailMessageFetcher
{
    private const BASE_URL = 'https://gmail.googleapis.com/gmail/v1/users/me';

    public function __construct(private readonly OAuthConnectionService $connections) {}

    /**
     * @return list<array{external_message_id: string, raw_content: string}>
     */
    public function fetchNewMessages(OAuthConnection $connection, string $notifiedHistoryId): array
    {
        $connection = $this->connections->ensureFreshToken($connection);

        $startHistoryId = $connection->last_history_id ?? $notifiedHistoryId;

        $messageIds = $this->listNewMessageIds($connection->access_token, $startHistoryId);

        $messages = array_map(
            fn (string $id) => [
                'external_message_id' => $id,
                'raw_content' => $this->fetchRawMessage($connection->access_token, $id),
            ],
            $messageIds,
        );

        $connection->update(['last_history_id' => $notifiedHistoryId]);

        return $messages;
    }

    /**
     * @return list<string>
     */
    private function listNewMessageIds(string $accessToken, string $startHistoryId): array
    {
        $messageIds = [];
        $pageToken = null;

        do {
            $response = Http::withToken($accessToken)->get(self::BASE_URL.'/history', array_filter([
                'startHistoryId' => $startHistoryId,
                'historyTypes' => 'messageAdded',
                'pageToken' => $pageToken,
            ]));

            if ($response->failed()) {
                throw new RuntimeException('Gmail history.list failed: '.$response->body());
            }

            foreach ($response->json('history', []) as $entry) {
                foreach ($entry['messagesAdded'] ?? [] as $added) {
                    $messageIds[] = $added['message']['id'];
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken !== null);

        return array_values(array_unique($messageIds));
    }

    private function fetchRawMessage(string $accessToken, string $messageId): string
    {
        $response = Http::withToken($accessToken)->get(self::BASE_URL."/messages/{$messageId}", [
            'format' => 'raw',
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Fetching Gmail message {$messageId} failed: ".$response->body());
        }

        return base64_decode(strtr($response->json('raw'), '-_', '+/'));
    }
}
