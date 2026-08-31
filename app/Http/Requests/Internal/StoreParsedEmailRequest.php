<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class StoreParsedEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gated by the EnsureInternalRequest middleware (shared-secret header), not user auth.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'relevant' => ['required', 'boolean'],
            'confidence' => ['required_if:relevant,true', 'nullable', 'numeric', 'between:0,1'],

            'segments' => ['required_if:relevant,true', 'array'],
            'segments.*.booking_reference' => ['nullable', 'string', 'max:255'],
            'segments.*.marketing_airline_iata' => ['required', 'string', 'size:2'],
            'segments.*.operating_airline_iata' => ['nullable', 'string', 'size:2'],
            'segments.*.marketing_flight_number' => ['required', 'string', 'max:6'],
            'segments.*.departure_airport_iata' => ['required', 'string', 'size:3'],
            'segments.*.departure_time_local' => ['required', 'date'],
            'segments.*.departure_timezone' => ['required', 'string', 'max:64'],
            'segments.*.departure_terminal' => ['nullable', 'string', 'max:32'],
            'segments.*.departure_gate' => ['nullable', 'string', 'max:32'],
            'segments.*.arrival_airport_iata' => ['required', 'string', 'size:3'],
            'segments.*.arrival_time_local' => ['required', 'date'],
            'segments.*.arrival_timezone' => ['required', 'string', 'max:64'],
            'segments.*.arrival_terminal' => ['nullable', 'string', 'max:32'],
            'segments.*.arrival_gate' => ['nullable', 'string', 'max:32'],
            'segments.*.cabin_class' => ['nullable', 'string', 'max:64'],
            'segments.*.seat' => ['nullable', 'string', 'max:8'],
            'segments.*.aircraft_type' => ['nullable', 'string', 'max:64'],
            'segments.*.price_amount' => ['nullable', 'numeric', 'min:0'],
            'segments.*.price_currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
