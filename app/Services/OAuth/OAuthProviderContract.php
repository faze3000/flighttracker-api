<?php

namespace App\Services\OAuth;

use App\Models\OAuthConnection;
use App\Services\OAuth\DataTransferObjects\OAuthTokenSet;
use App\Services\OAuth\DataTransferObjects\OAuthWatchRegistration;

interface OAuthProviderContract
{
    /**
     * Build the URL the user is sent to in order to grant mail-read access.
     */
    public function authorizationUrl(string $state): string;

    /**
     * Exchange an authorization code (from the callback) for tokens.
     */
    public function exchangeCode(string $code): OAuthTokenSet;

    /**
     * Exchange a refresh token for a fresh access token.
     */
    public function refreshAccessToken(string $refreshToken): OAuthTokenSet;

    /**
     * Register (or renew) the mailbox watch/subscription that makes the
     * provider send us push notifications, and report back the connected
     * account's email — the only thing a push notification carries that we
     * can match back to a user.
     */
    public function setUpWatch(string $accessToken): OAuthWatchRegistration;

    /**
     * Tear down the watch/subscription and revoke the grant on the
     * provider's side where possible. Best-effort: callers should not fail
     * the disconnect flow if the provider is unreachable.
     */
    public function revoke(OAuthConnection $connection): void;
}
