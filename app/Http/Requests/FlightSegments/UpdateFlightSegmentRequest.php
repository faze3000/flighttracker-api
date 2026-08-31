<?php

namespace App\Http\Requests\FlightSegments;

use App\Enums\FlightSegmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateFlightSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'booking_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'marketing_airline_iata' => ['sometimes', 'string', 'size:2'],
            'operating_airline_iata' => ['sometimes', 'nullable', 'string', 'size:2'],
            'marketing_flight_number' => ['sometimes', 'string', 'max:6'],
            'departure_airport_iata' => ['sometimes', 'string', 'size:3'],
            'departure_time_local' => ['sometimes', 'date'],
            'departure_timezone' => ['sometimes', 'string', 'max:64'],
            'departure_terminal' => ['sometimes', 'nullable', 'string', 'max:32'],
            'departure_gate' => ['sometimes', 'nullable', 'string', 'max:32'],
            'arrival_airport_iata' => ['sometimes', 'string', 'size:3'],
            'arrival_time_local' => ['sometimes', 'date'],
            'arrival_timezone' => ['sometimes', 'string', 'max:64'],
            'arrival_terminal' => ['sometimes', 'nullable', 'string', 'max:32'],
            'arrival_gate' => ['sometimes', 'nullable', 'string', 'max:32'],
            'cabin_class' => ['sometimes', 'nullable', 'string', 'max:64'],
            'seat' => ['sometimes', 'nullable', 'string', 'max:8'],
            'aircraft_type' => ['sometimes', 'nullable', 'string', 'max:64'],
            'status' => ['sometimes', new Enum(FlightSegmentStatus::class)],
            'price_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ];
    }
}
