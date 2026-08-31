<?php

namespace App\Services\Email;

use App\Enums\RawEmailSource;
use App\Models\RawEmail;
use App\Models\User;
use App\Services\Queue\RedisJsonQueue;
use Carbon\CarbonInterface;

/**
 * Single entry point for turning "we received an email" (from any of the
 * three ingestion triggers) into a stored RawEmail row plus a parse job on
 * the queue Python consumes from.
 */
class RawEmailIngestor
{
    public function __construct(private readonly RedisJsonQueue $queue) {}

    public function ingest(
        User $user,
        RawEmailSource $source,
        ?string $externalMessageId,
        ?string $rawContent,
        CarbonInterface $receivedAt,
    ): RawEmail {
        $rawEmail = RawEmail::firstOrCreate(
            [
                'user_id' => $user->id,
                'source' => $source,
                'external_message_id' => $externalMessageId,
            ],
            [
                'status' => 'pending',
                'raw_content' => $rawContent,
                'received_at' => $receivedAt,
            ],
        );

        if ($rawEmail->wasRecentlyCreated) {
            $this->queue->push(config('flighttracker.queues.parse_jobs'), [
                'raw_email_id' => $rawEmail->id,
                'user_id' => $user->id,
                'source' => $source->value,
                'raw_content' => $rawContent,
                'received_at' => $receivedAt->toIso8601String(),
            ]);
        }

        return $rawEmail;
    }
}
