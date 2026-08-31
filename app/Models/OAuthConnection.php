<?php

namespace App\Models;

use App\Enums\OAuthConnectionStatus;
use App\Enums\OAuthProvider;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'provider_account_email',
    'provider_subscription_id',
    'watch_expiration',
    'last_history_id',
    'status',
])]
#[Hidden(['access_token', 'refresh_token'])]
class OAuthConnection extends Model
{
    use HasFactory;

    // Eloquent's naming convention would guess "o_auth_connections" for this
    // class name (it snake_cases each capital in "OAuth" individually).
    protected $table = 'oauth_connections';

    protected function casts(): array
    {
        return [
            'provider' => OAuthProvider::class,
            'status' => OAuthConnectionStatus::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'watch_expiration' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }
}
