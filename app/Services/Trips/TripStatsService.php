<?php

namespace App\Services\Trips;

use App\Enums\FlightSegmentStatus;
use App\Models\FlightSegment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Lifetime stats and yearly recaps computed from flight_segments +
 * airports reference data (spec §6). Cancelled segments never flew, so
 * they're excluded from every figure.
 */
class TripStatsService
{
    public function __construct(private readonly DistanceCalculator $distance) {}

    /**
     * @return array<string, mixed>
     */
    public function lifetime(User $user): array
    {
        return $this->summarize($this->baseQuery($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function recap(User $user, int $year): array
    {
        return $this->summarize(
            $this->baseQuery($user)->whereYear('departure_time_local', $year),
        );
    }

    /**
     * @return HasMany<FlightSegment, User>
     */
    private function baseQuery(User $user): HasMany
    {
        return $user->flightSegments()
            ->where('status', '!=', FlightSegmentStatus::Cancelled)
            ->with(['departureAirport', 'arrivalAirport']);
    }

    /**
     * @param  HasMany<FlightSegment, User>  $query
     * @return array<string, mixed>
     */
    private function summarize(HasMany $query): array
    {
        /** @var Collection<int, FlightSegment> $segments */
        $segments = $query->get();

        $totalMiles = $segments->sum(function (FlightSegment $segment) {
            if ($segment->departureAirport === null || $segment->arrivalAirport === null) {
                return 0;
            }

            return $this->distance->milesBetween(
                (float) $segment->departureAirport->lat,
                (float) $segment->departureAirport->lng,
                (float) $segment->arrivalAirport->lat,
                (float) $segment->arrivalAirport->lng,
            );
        });

        $countries = $segments
            ->flatMap(fn (FlightSegment $s) => [$s->departureAirport?->country, $s->arrivalAirport?->country])
            ->filter()
            ->unique()
            ->values();

        $airlines = $segments->pluck('marketing_airline_iata')->filter()->unique()->values();

        $cabinMix = $segments
            ->filter(fn (FlightSegment $s) => $s->cabin_class !== null)
            ->countBy('cabin_class');

        return [
            'total_flights' => $segments->count(),
            'total_miles' => (int) round($totalMiles),
            'countries_visited' => $countries->count(),
            'countries' => $countries,
            'airlines_flown' => $airlines->count(),
            'airlines' => $airlines,
            'cabin_mix' => $cabinMix,
        ];
    }
}
