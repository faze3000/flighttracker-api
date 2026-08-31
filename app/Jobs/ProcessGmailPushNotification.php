<?php

namespace App\Jobs;

use App\Enums\RawEmailSource;
use App\Models\OAuthConnection;
use App\Services\Email\GmailMessageFetcher;
use App\Services\Email\RawEmailIngestor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Does the actual Gmail API work for a push notification. Kept off the
 * webhook request itself so the controller can ack Pub/Sub immediately —
 * Pub/Sub retries (and can eventually give up) if the endpoint doesn't
 * respond within its ack deadline.
 */
class ProcessGmailPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $oauthConnectionId,
        private readonly string $historyId,
    ) {}

    public function handle(GmailMessageFetcher $fetcher, RawEmailIngestor $ingestor): void
    {
        $connection = OAuthConnection::find($this->oauthConnectionId);

        if ($connection === null) {
            return;
        }

        $messages = $fetcher->fetchNewMessages($connection, $this->historyId);

        foreach ($messages as $message) {
            $ingestor->ingest(
                user: $connection->user,
                source: RawEmailSource::Gmail,
                externalMessageId: $message['external_message_id'],
                rawContent: $message['raw_content'],
                receivedAt: now(),
            );
        }
    }
}
