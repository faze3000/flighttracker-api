<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Decides which Trip a newly-extracted flight segment belongs to. Trip
 * grouping is explicitly an open question in the spec (§6/§11.3 — "auto-group
 * separately-booked trips by date/location proximity, or one-trip-per-booking
 * with manual merge?"); this implements a simple, real default rather than
 * leaving the callback with nowhere to attach a segment:
 *
 *   1. Reuse a trip that already has a segment with the same booking
 *      reference (covers multi-segment itineraries from one PNR).
 *   2. Otherwise reuse a trip whose date range is within a day of this
 *      segment's departure (covers separately-booked flights on the same trip
 *      — e.g. a hotel-adjacent one-way booked apart from the return).
 *   3. Otherwise start a new trip.
 *
 * The user can always merge/split manually later via the Trips API.
 */
class TripGroupingService
{
    private const PROXIMITY_DAYS = 1;

    public function assignSegmentToTrip(
        User $user,
        ?string $bookingReference,
        string $departureAirportIata,
        string $arrivalAirportIata,
        CarbonImmutable $departureDate,
        CarbonImmutable $arrivalDate,
    ): Trip {
        $trip = $this->findByBookingReference($user, $bookingReference)
            ?? $this->findByDateProximity($user, $departureDate, $arrivalDate)
            ?? $this->createTrip($user, $departureAirportIata, $arrivalAirportIata, $departureDate, $arrivalDate);

        $trip->start_date = min($trip->start_date, $departureDate->toDateString());
        $trip->end_date = max($trip->end_date, $arrivalDate->toDateString());
        $trip->save();

        return $trip;
    }

    private function findByBookingReference(User $user, ?string $bookingReference): ?Trip
    {
        if ($bookingReference === null) {
            return null;
        }

        return Trip::query()
            ->where('user_id', $user->id)
            ->whereHas('flightSegments', fn ($query) => $query->where('booking_reference', $bookingReference))
            ->first();
    }

    private function findByDateProximity(User $user, CarbonImmutable $departureDate, CarbonImmutable $arrivalDate): ?Trip
    {
        return Trip::query()
            ->where('user_id', $user->id)
            ->where('start_date', '<=', $arrivalDate->addDays(self::PROXIMITY_DAYS)->toDateString())
            ->where('end_date', '>=', $departureDate->subDays(self::PROXIMITY_DAYS)->toDateString())
            ->first();
    }

    private function createTrip(
        User $user,
        string $departureAirportIata,
        string $arrivalAirportIata,
        CarbonImmutable $departureDate,
        CarbonImmutable $arrivalDate,
    ): Trip {
        return $user->trips()->create([
            'label' => "{$departureAirportIata} \u{2192} {$arrivalAirportIata}",
            'start_date' => $departureDate->toDateString(),
            'end_date' => $arrivalDate->toDateString(),
            'status' => TripStatus::Upcoming,
        ]);
    }
}
