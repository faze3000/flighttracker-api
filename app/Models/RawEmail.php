<?php

namespace App\Models;

use App\Enums\RawEmailSource;
use App\Enums\RawEmailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'source',
    'external_message_id',
    'status',
    'raw_content',
    'received_at',
    'parsed_at',
])]
class RawEmail extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => RawEmailSource::class,
            'status' => RawEmailStatus::class,
            'received_at' => 'datetime',
            'parsed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<FlightSegment, $this>
     */
    public function flightSegments(): HasMany
    {
        return $this->hasMany(FlightSegment::class, 'source_email_id');
    }
}
