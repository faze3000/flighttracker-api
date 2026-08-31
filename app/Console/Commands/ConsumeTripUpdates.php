<?php

namespace App\Console\Commands;

use App\Services\Queue\RedisJsonQueue;
use App\Services\TripUpdates\TripUpdateApplier;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

/**
 * Dedicated worker process for the trip-updates queue (spec §4/§5): plain
 * JSON on a Redis list, consumed with a raw BLPOP rather than Laravel's
 * job system, since it's produced by another (non-Laravel) service.
 *
 * Run as its own long-lived process, e.g.:
 *   php artisan queue:work-trip-updates
 */
class ConsumeTripUpdates extends Command
{
    protected $signature = 'queue:work-trip-updates
        {--once : Process a single message and exit (for testing)}
        {--timeout=5 : Seconds to block waiting for the next message}';

    protected $description = 'Consume the trip-updates Redis queue and apply changes to flight segments';

    public function handle(RedisJsonQueue $queue, TripUpdateApplier $applier): int
    {
        $queueKey = config('flighttracker.queues.trip_updates');
        $timeout = (int) $this->option('timeout');

        $this->info("Listening on trip-updates queue [{$queueKey}]...");

        do {
            $payload = $queue->blockingPop($queueKey, $timeout);

            if ($payload !== null) {
                $this->applyPayload($applier, $payload);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyPayload(TripUpdateApplier $applier, array $payload): void
    {
        try {
            $applier->apply($payload);
        } catch (RuntimeException $e) {
            $this->warn($e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->error("Failed to apply trip update: {$e->getMessage()}");
        }
    }
}
