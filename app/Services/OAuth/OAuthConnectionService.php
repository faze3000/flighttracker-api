<?php

namespace App\Services\OAuth;

use App\Enums\OAuthConnectionStatus;
use App\Enums\OAuthProvider;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthConnectionService
{
    private const STATE_TTL_MINUTES = 10;

    public function __construct(private readonly OAuthProviderManager $providers) {}

    /**
     * Start a connect flow: mint a one-time state token bound to this user
     * and provider, and return the URL the client should open.
     *
     * The connect endpoint requires an authenticated (Sanctum) request, but
     * the callback is hit directly by Google/Microsoft with no auth header —
     * the state token is how we recover which user the callback belongs to.
     */
    public function startConnect(User $user, OAuthProvider $provider): string
    {
        $state = Str::random(40);

        Cache::put(
            $this->stateCacheKey($state),
            ['user_id' => $user->id, 'provider' => $provider->value],
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        return $this->providers->resolve($provider)->authorizationUrl($state);
    }

    /**
     * Complete a connect flow from the provider's redirect.
     */
    public function handleCallback(string $provider, string $state, string $code): OAuthConnection
    {
        $stateData = Cache::pull($this->stateCacheKey($state));

        if ($stateData === null || $stateData['provider'] !== $provider) {
            throw new RuntimeException('Invalid or expired OAuth state.');
        }

        $providerEnum = OAuthProvider::from($provider);
        $providerService = $this->providers->resolve($providerEnum);
        $tokens = $providerService->exchangeCode($code);

        $attributes = [
            'access_token' => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken,
            'token_expires_at' => $tokens->expiresAt,
            'status' => OAuthConnectionStatus::Active,
        ];

        // The watch/subscription is what makes push notifications happen at
        // all, but its absence shouldn't block a connection whose OAuth
        // grant otherwise succeeded — the account just won't get inbound
        // pushes until the next renewal sweep picks it up.
        try {
            $watch = $providerService->setUpWatch($tokens->accessToken);
            $attributes['provider_account_email'] = $watch->accountEmail;
            $attributes['provider_subscription_id'] = $watch->subscriptionId;
            $attributes['watch_expiration'] = $watch->expiresAt;
            $attributes['last_history_id'] = $watch->historyId;
        } catch (RuntimeException $e) {
            report($e);
        }

        return OAuthConnection::updateOrCreate(
            ['user_id' => $stateData['user_id'], 'provider' => $providerEnum],
            $attributes,
        );
    }

    /**
     * Return a connection with a guaranteed-fresh access token, refreshing
     * it against the provider first if it has expired (or is about to).
     */
    public function ensureFreshToken(OAuthConnection $connection): OAuthConnection
    {
        if (! $connection->isExpired()) {
            return $connection;
        }

        try {
            $tokens = $this->providers->resolve($connection->provider)->refreshAccessToken($connection->refresh_token);
        } catch (RuntimeException $e) {
            $connection->update(['status' => OAuthConnectionStatus::Error]);
            throw $e;
        }

        $connection->update([
            'access_token' => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken ?? $connection->refresh_token,
            'token_expires_at' => $tokens->expiresAt,
            'status' => OAuthConnectionStatus::Active,
        ]);

        return $connection;
    }

    /**
     * Renew a connection's watch/subscription before it lapses. Used by the
     * scheduled sweep (App\Console\Commands\RenewOAuthWatches) — Gmail
     * watches and Graph mail subscriptions both expire within days.
     */
    public function renewWatch(OAuthConnection $connection): void
    {
        $connection = $this->ensureFreshToken($connection);

        $watch = $this->providers->resolve($connection->provider)->setUpWatch($connection->access_token);

        $connection->update([
            'provider_account_email' => $watch->accountEmail,
            'provider_subscription_id' => $watch->subscriptionId,
            'watch_expiration' => $watch->expiresAt,
            // Only seed the cursor if we never had one — re-registering a
            // still-live watch shouldn't fast-forward past messages the
            // history-sync fetcher hasn't processed yet.
            'last_history_id' => $connection->last_history_id ?? $watch->historyId,
        ]);
    }

    public function disconnect(OAuthConnection $connection): void
    {
        try {
            $this->providers->resolve($connection->provider)->revoke($connection);
        } catch (RuntimeException) {
            // Best-effort: proceed to remove our own record even if the
            // provider-side revoke call fails (e.g. token already invalid).
        }

        $connection->delete();
    }

    private function stateCacheKey(string $state): string
    {
        return "oauth-connect-state:{$state}";
    }
}
