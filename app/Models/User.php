<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<OAuthConnection, $this>
     */
    public function oauthConnections(): HasMany
    {
        return $this->hasMany(OAuthConnection::class);
    }

    /**
     * @return HasOne<ForwardingAddress, $this>
     */
    public function forwardingAddress(): HasOne
    {
        return $this->hasOne(ForwardingAddress::class);
    }

    /**
     * @return HasMany<RawEmail, $this>
     */
    public function rawEmails(): HasMany
    {
        return $this->hasMany(RawEmail::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * @return HasMany<FlightSegment, $this>
     */
    public function flightSegments(): HasMany
    {
        return $this->hasMany(FlightSegment::class);
    }
}
