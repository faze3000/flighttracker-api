<?php

namespace App\Console\Commands;

use App\Enums\OAuthConnectionStatus;
use App\Models\OAuthConnection;
use App\Services\OAuth\OAuthConnectionService;
use Illuminate\Console\Command;
use RuntimeException;

class RenewOAuthWatches extends Command
{
    protected $signature = 'oauth:renew-watches';

    protected $description = 'Renew Gmail/Outlook mailbox watches that are about to expire';

    public function handle(OAuthConnectionService $connections): int
    {
        $expiringSoon = OAuthConnection::query()
            ->where('status', OAuthConnectionStatus::Active)
            ->where('watch_expiration', '<=', now()->addDay())
            ->get();

        foreach ($expiringSoon as $connection) {
            try {
                $connections->renewWatch($connection);
            } catch (RuntimeException $e) {
                report($e);
                $this->error("Failed to renew watch for connection {$connection->id}: {$e->getMessage()}");
            }
        }

        $this->info("Renewed {$expiringSoon->count()} watch(es).");

        return self::SUCCESS;
    }
}
