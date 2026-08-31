<?php

namespace App\Services\OAuth\DataTransferObjects;

use Carbon\CarbonImmutable;

final readonly class OAuthTokenSet
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?CarbonImmutable $expiresAt,
    ) {}
}
