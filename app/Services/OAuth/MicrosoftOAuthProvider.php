<?php

namespace App\Services\OAuth;

use App\Models\OAuthConnection;
use App\Services\OAuth\DataTransferObjects\OAuthTokenSet;
use App\Services\OAuth\DataTransferObjects\OAuthWatchRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftOAuthProvider implements OAuthProviderContract
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    private const SCOPES = [
        'offline_access',
        'Mail.Read',
        'User.Read',
    ];

    // Graph caps a mail-resource subscription's lifetime at ~4230 minutes (~2.94 days).
    private const SUBSCRIPTION_LIFETIME_MINUTES = 4230;

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.outlook.client_id'),
            'redirect_uri' => config('services.outlook.redirect'),
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => implode(' ', self::SCOPES),
            'state' => $state,
        ]);

        return $this->authorizeUrl().'?'.$query;
    }

    public function exchangeCode(string $code): OAuthTokenSet
    {
        $response = Http::asForm()->post($this->tokenUrl(), [
            'client_id' => config('services.outlook.client_id'),
            'client_secret' => config('services.outlook.client_secret'),
            'redirect_uri' => config('services.outlook.redirect'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'scope' => implode(' ', self::SCOPES),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Microsoft token exchange failed: '.$response->body());
        }

        return $this->tokenSetFromResponse($response->json());
    }

    public function refreshAccessToken(string $refreshToken): OAuthTokenSet
    {
        $response = Http::asForm()->post($this->tokenUrl(), [
            'client_id' => config('services.outlook.client_id'),
            'client_secret' => config('services.outlook.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', self::SCOPES),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Microsoft token refresh failed: '.$response->body());
        }

        return $this->tokenSetFromResponse($response->json());
    }

    public function setUpWatch(string $accessToken): OAuthWatchRegistration
    {
        $me = Http::withToken($accessToken)->get(self::GRAPH_BASE.'/me', [
            '$select' => 'mail,userPrincipalName',
        ]);

        if ($me->failed()) {
            throw new RuntimeException('Fetching Microsoft Graph profile failed: '.$me->body());
        }

        $expiresAt = CarbonImmutable::now()->addMinutes(self::SUBSCRIPTION_LIFETIME_MINUTES);

        $subscription = Http::withToken($accessToken)->post(self::GRAPH_BASE.'/subscriptions', [
            'changeType' => 'created',
            'notificationUrl' => config('services.outlook.notification_url'),
            'resource' => 'me/mailFolders/Inbox/messages',
            'expirationDateTime' => $expiresAt->toIso8601String(),
            'clientState' => config('services.outlook.webhook_client_state'),
        ]);

        if ($subscription->failed()) {
            throw new RuntimeException('Creating Microsoft Graph subscription failed: '.$subscription->body());
        }

        return new OAuthWatchRegistration(
            accountEmail: $me->json('mail') ?? $me->json('userPrincipalName'),
            subscriptionId: $subscription->json('id'),
            expiresAt: $expiresAt,
        );
    }

    public function revoke(OAuthConnection $connection): void
    {
        // Microsoft's v2 identity platform has no per-token revoke endpoint for
        // confidential clients (only /me/revokeSignInSessions, which revokes every
        // session for the user, not just this grant), so the refresh token can't be
        // invalidated remotely. Deleting our stored copy prevents further use; the
        // one thing we *can* and should tear down remotely is the subscription,
        // so Graph stops sending notifications for a connection we no longer track.
        if ($connection->provider_subscription_id !== null) {
            Http::withToken($connection->access_token)
                ->delete(self::GRAPH_BASE.'/subscriptions/'.$connection->provider_subscription_id);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tokenSetFromResponse(array $payload): OAuthTokenSet
    {
        if (empty($payload['refresh_token'])) {
            throw new RuntimeException('Microsoft did not return a refresh token.');
        }

        return new OAuthTokenSet(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresAt: CarbonImmutable::now()->addSeconds((int) $payload['expires_in']),
        );
    }

    private function authorizeUrl(): string
    {
        $tenant = config('services.outlook.tenant');

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize";
    }

    private function tokenUrl(): string
    {
        $tenant = config('services.outlook.tenant');

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
    }
}
