<?php

namespace App\Models;

use App\Enums\FlightSegmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_id',
    'user_id',
    'source_email_id',
    'booking_reference',
    'marketing_airline_iata',
    'operating_airline_iata',
    'marketing_flight_number',
    'departure_airport_iata',
    'departure_time_local',
    'departure_timezone',
    'departure_terminal',
    'departure_gate',
    'arrival_airport_iata',
    'arrival_time_local',
    'arrival_timezone',
    'arrival_terminal',
    'arrival_gate',
    'cabin_class',
    'seat',
    'aircraft_type',
    'status',
    'price_amount',
    'price_currency',
    'confidence_score',
    'needs_review',
])]
class FlightSegment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'departure_time_local' => 'datetime',
            'arrival_time_local' => 'datetime',
            'status' => FlightSegmentStatus::class,
            'price_amount' => 'decimal:2',
            'confidence_score' => 'decimal:3',
            'needs_review' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<RawEmail, $this>
     */
    public function sourceEmail(): BelongsTo
    {
        return $this->belongsTo(RawEmail::class, 'source_email_id');
    }

    /**
     * @return BelongsTo<Airport, $this>
     */
    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'departure_airport_iata', 'iata_code');
    }

    /**
     * @return BelongsTo<Airport, $this>
     */
    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_iata', 'iata_code');
    }

    /**
     * @return BelongsTo<Airline, $this>
     */
    public function marketingAirline(): BelongsTo
    {
        return $this->belongsTo(Airline::class, 'marketing_airline_iata', 'iata_code');
    }
}
