<?php

namespace App\Jobs;

use App\Enums\RawEmailSource;
use App\Models\OAuthConnection;
use App\Services\Email\OutlookMessageFetcher;
use App\Services\Email\RawEmailIngestor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOutlookChangeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $subscriptionId,
        private readonly string $messageId,
    ) {}

    public function handle(OutlookMessageFetcher $fetcher, RawEmailIngestor $ingestor): void
    {
        $connection = OAuthConnection::query()->where('provider_subscription_id', $this->subscriptionId)->first();

        if ($connection === null) {
            return;
        }

        $message = $fetcher->fetchMessage($connection, $this->messageId);

        $ingestor->ingest(
            user: $connection->user,
            source: RawEmailSource::Outlook,
            externalMessageId: $message['external_message_id'],
            rawContent: $message['raw_content'],
            receivedAt: now(),
        );
    }
}
