<?php

namespace App\Services\OAuth;

use App\Enums\OAuthProvider;
use Illuminate\Contracts\Container\Container;

class OAuthProviderManager
{
    public function __construct(private readonly Container $container) {}

    public function resolve(OAuthProvider $provider): OAuthProviderContract
    {
        return match ($provider) {
            OAuthProvider::Gmail => $this->container->make(GoogleOAuthProvider::class),
            OAuthProvider::Outlook => $this->container->make(MicrosoftOAuthProvider::class),
        };
    }
}
