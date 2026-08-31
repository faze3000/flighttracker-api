<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Redis;

/**
 * The parse-job and trip-updates queues cross the Laravel/Python boundary,
 * so they carry plain JSON on a Redis list rather than Laravel's serialized
 * queue jobs — pushed/popped with raw list commands on an unprefixed
 * connection (see config/database.php's redis.queues connection) so a
 * plain Python redis client sees the same keys we do.
 */
class RedisJsonQueue
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function push(string $queueKey, array $payload): void
    {
        Redis::connection('queues')->rpush($queueKey, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Block until a payload is available, or $timeoutSeconds elapses.
     *
     * @return array<string, mixed>|null
     */
    public function blockingPop(string $queueKey, int $timeoutSeconds): ?array
    {
        $result = Redis::connection('queues')->blpop([$queueKey], $timeoutSeconds);

        if (empty($result)) {
            return null;
        }

        // BLPOP returns [key, value].
        return json_decode($result[1], associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
