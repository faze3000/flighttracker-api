<?php

namespace App\Services\Parsing;

use App\Enums\FlightSegmentStatus;
use App\Enums\RawEmailStatus;
use App\Models\FlightSegment;
use App\Models\RawEmail;
use App\Services\Trips\TripGroupingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Applies the parser's extraction result (POST body of
 * PATCH /internal/emails/{id}/parsed) to the database: creates flight
 * segments (grouped into trips), flags low-confidence ones for review, and
 * marks the source email parsed/irrelevant.
 */
class ParsedEmailProcessor
{
    public function __construct(private readonly TripGroupingService $tripGrouping) {}

    /**
     * @param  array{relevant: bool, confidence?: float|null, segments?: array<int, array<string, mixed>>}  $data
     * @return Collection<int, FlightSegment>
     */
    public function process(RawEmail $rawEmail, array $data): Collection
    {
        // Idempotent: a retried callback (network blip, at-least-once delivery)
        // must not create duplicate segments for an email already processed.
        if ($rawEmail->status !== RawEmailStatus::Pending) {
            return $rawEmail->flightSegments;
        }

        if (! $data['relevant']) {
            $rawEmail->update([
                'status' => RawEmailStatus::Irrelevant,
                'parsed_at' => now(),
            ]);

            return collect();
        }

        $needsReview = $data['confidence'] < config('flighttracker.confidence_review_threshold');

        $segments = DB::transaction(function () use ($rawEmail, $data, $needsReview) {
            $segments = collect($data['segments'])->map(
                fn (array $segment) => $this->createSegment($rawEmail, $segment, $data['confidence'], $needsReview),
            );

            $rawEmail->update([
                'status' => RawEmailStatus::Parsed,
                'parsed_at' => now(),
            ]);

            return $segments;
        });

        return $segments;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private function createSegment(RawEmail $rawEmail, array $segment, float $confidence, bool $needsReview): FlightSegment
    {
        $departureTime = CarbonImmutable::parse($segment['departure_time_local']);
        $arrivalTime = CarbonImmutable::parse($segment['arrival_time_local']);

        $trip = $this->tripGrouping->assignSegmentToTrip(
            user: $rawEmail->user,
            bookingReference: $segment['booking_reference'] ?? null,
            departureAirportIata: $segment['departure_airport_iata'],
            arrivalAirportIata: $segment['arrival_airport_iata'],
            departureDate: $departureTime,
            arrivalDate: $arrivalTime,
        );

        return FlightSegment::create([
            ...$segment,
            'trip_id' => $trip->id,
            'user_id' => $rawEmail->user_id,
            'source_email_id' => $rawEmail->id,
            'status' => FlightSegmentStatus::Confirmed,
            'confidence_score' => $confidence,
            'needs_review' => $needsReview,
        ]);
    }
}
