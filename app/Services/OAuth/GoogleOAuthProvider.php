<?php

namespace App\Services\OAuth;

use App\Models\OAuthConnection;
use App\Services\OAuth\DataTransferObjects\OAuthTokenSet;
use App\Services\OAuth\DataTransferObjects\OAuthWatchRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleOAuthProvider implements OAuthProviderContract
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const PROFILE_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/profile';

    private const WATCH_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/watch';

    private const STOP_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/stop';

    private const SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.gmail.client_id'),
            'redirect_uri' => config('services.gmail.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL.'?'.$query;
    }

    public function exchangeCode(string $code): OAuthTokenSet
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.gmail.client_id'),
            'client_secret' => config('services.gmail.client_secret'),
            'redirect_uri' => config('services.gmail.redirect'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google token exchange failed: '.$response->body());
        }

        $payload = $response->json();

        // Google only returns refresh_token on the first consent (access_type=offline
        // + prompt=consent forces it); a re-connect without revoking first can omit it.
        if (empty($payload['refresh_token'])) {
            throw new RuntimeException('Google did not return a refresh token; revoke prior access and reconnect.');
        }

        return new OAuthTokenSet(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresAt: CarbonImmutable::now()->addSeconds((int) $payload['expires_in']),
        );
    }

    public function refreshAccessToken(string $refreshToken): OAuthTokenSet
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.gmail.client_id'),
            'client_secret' => config('services.gmail.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google token refresh failed: '.$response->body());
        }

        $payload = $response->json();

        return new OAuthTokenSet(
            accessToken: $payload['access_token'],
            // Google normally doesn't rotate the refresh token on refresh; keep the one we had.
            refreshToken: $payload['refresh_token'] ?? $refreshToken,
            expiresAt: CarbonImmutable::now()->addSeconds((int) $payload['expires_in']),
        );
    }

    /**
     * Gmail push notifications only arrive for a mailbox actively watched via
     * users.watch (renews every ~7 days) against a Pub/Sub topic. The watch
     * response has no email address, so a separate profile call gets it.
     */
    public function setUpWatch(string $accessToken): OAuthWatchRegistration
    {
        $profile = Http::withToken($accessToken)->get(self::PROFILE_URL);

        if ($profile->failed()) {
            throw new RuntimeException('Fetching Gmail profile failed: '.$profile->body());
        }

        $watch = Http::withToken($accessToken)->post(self::WATCH_URL, [
            'topicName' => config('services.gmail.pubsub_topic'),
            'labelIds' => ['INBOX'],
        ]);

        if ($watch->failed()) {
            throw new RuntimeException('Registering Gmail watch failed: '.$watch->body());
        }

        return new OAuthWatchRegistration(
            accountEmail: $profile->json('emailAddress'),
            subscriptionId: null,
            expiresAt: CarbonImmutable::createFromTimestampMs((int) $watch->json('expiration')),
            historyId: (string) $watch->json('historyId'),
        );
    }

    public function revoke(OAuthConnection $connection): void
    {
        Http::withToken($connection->access_token)->post(self::STOP_URL);

        Http::asForm()->post(self::REVOKE_URL, [
            'token' => $connection->refresh_token,
        ]);
    }
}
