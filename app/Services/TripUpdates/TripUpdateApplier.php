<?php

namespace App\Services\TripUpdates;

use App\Enums\FlightSegmentStatus;
use App\Enums\TripStatus;
use App\Models\FlightSegment;
use RuntimeException;

/**
 * Applies one message from the trip-updates queue (schedule changes, gate
 * changes, cancellations for a tracked flight) to the matching segment —
 * and, for a cancellation, cascades to the trip if every one of its
 * segments has now been cancelled.
 */
class TripUpdateApplier
{
    private const UPDATABLE_FIELDS = [
        'status',
        'departure_time_local',
        'departure_terminal',
        'departure_gate',
        'arrival_time_local',
        'arrival_terminal',
        'arrival_gate',
    ];

    /**
     * @param  array{
     *     booking_reference?: string|null,
     *     marketing_airline_iata: string,
     *     marketing_flight_number: string,
     *     departure_airport_iata: string,
     *     departure_date: string,
     *     changes: array<string, mixed>,
     * }  $payload
     */
    public function apply(array $payload): void
    {
        $segment = $this->findSegment($payload);

        if ($segment === null) {
            throw new RuntimeException(
                "No matching flight segment for {$payload['marketing_airline_iata']}{$payload['marketing_flight_number']} on {$payload['departure_date']}.",
            );
        }

        $changes = array_intersect_key($payload['changes'], array_flip(self::UPDATABLE_FIELDS));
        $segment->update($changes);

        if ($segment->status === FlightSegmentStatus::Cancelled) {
            $this->cascadeCancellationToTrip($segment);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findSegment(array $payload): ?FlightSegment
    {
        $query = FlightSegment::query()
            ->where('marketing_airline_iata', $payload['marketing_airline_iata'])
            ->where('marketing_flight_number', $payload['marketing_flight_number'])
            ->where('departure_airport_iata', $payload['departure_airport_iata'])
            ->whereDate('departure_time_local', $payload['departure_date']);

        if (! empty($payload['booking_reference'])) {
            $query->where('booking_reference', $payload['booking_reference']);
        }

        return $query->first();
    }

    private function cascadeCancellationToTrip(FlightSegment $segment): void
    {
        $trip = $segment->trip;

        $allCancelled = $trip->flightSegments()
            ->where('status', '!=', FlightSegmentStatus::Cancelled)
            ->doesntExist();

        if ($allCancelled) {
            $trip->update(['status' => TripStatus::Cancelled]);
        }
    }
}
