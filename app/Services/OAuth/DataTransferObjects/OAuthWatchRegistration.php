<?php

namespace App\Services\OAuth\DataTransferObjects;

use Carbon\CarbonImmutable;

final readonly class OAuthWatchRegistration
{
    public function __construct(
        public string $accountEmail,
        public ?string $subscriptionId,
        public ?CarbonImmutable $expiresAt,
        // Gmail history.list cursor at the moment the watch was (re)registered; null for Outlook.
        public ?string $historyId = null,
    ) {}
}
