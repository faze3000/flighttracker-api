<?php

namespace App\Http\Resources;

use App\Models\FlightSegment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlightSegment
 */
class FlightSegmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'booking_reference' => $this->booking_reference,
            'marketing_airline_iata' => $this->marketing_airline_iata,
            'operating_airline_iata' => $this->operating_airline_iata,
            'marketing_flight_number' => $this->marketing_flight_number,
            'departure' => [
                'airport_iata' => $this->departure_airport_iata,
                'time_local' => $this->departure_time_local,
                'timezone' => $this->departure_timezone,
                'terminal' => $this->departure_terminal,
                'gate' => $this->departure_gate,
            ],
            'arrival' => [
                'airport_iata' => $this->arrival_airport_iata,
                'time_local' => $this->arrival_time_local,
                'timezone' => $this->arrival_timezone,
                'terminal' => $this->arrival_terminal,
                'gate' => $this->arrival_gate,
            ],
            'cabin_class' => $this->cabin_class,
            'seat' => $this->seat,
            'aircraft_type' => $this->aircraft_type,
            'status' => $this->status,
            'price_amount' => $this->price_amount,
            'price_currency' => $this->price_currency,
            'confidence_score' => $this->confidence_score,
            'needs_review' => $this->needs_review,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
