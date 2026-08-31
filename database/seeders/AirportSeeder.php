<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

/**
 * A small, realistic set of major airports — enough reference data to
 * develop and test trip/stats features against. Not exhaustive; a real
 * deployment would load this from a proper IATA airport dataset.
 */
class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['iata_code' => 'LHR', 'name' => 'London Heathrow', 'city' => 'London', 'country' => 'United Kingdom', 'timezone' => 'Europe/London', 'lat' => 51.4700, 'lng' => -0.4543],
            ['iata_code' => 'LGW', 'name' => 'London Gatwick', 'city' => 'London', 'country' => 'United Kingdom', 'timezone' => 'Europe/London', 'lat' => 51.1537, 'lng' => -0.1821],
            ['iata_code' => 'JFK', 'name' => 'John F. Kennedy International', 'city' => 'New York', 'country' => 'United States', 'timezone' => 'America/New_York', 'lat' => 40.6413, 'lng' => -73.7781],
            ['iata_code' => 'LAX', 'name' => 'Los Angeles International', 'city' => 'Los Angeles', 'country' => 'United States', 'timezone' => 'America/Los_Angeles', 'lat' => 33.9416, 'lng' => -118.4085],
            ['iata_code' => 'SFO', 'name' => 'San Francisco International', 'city' => 'San Francisco', 'country' => 'United States', 'timezone' => 'America/Los_Angeles', 'lat' => 37.6213, 'lng' => -122.3790],
            ['iata_code' => 'DXB', 'name' => 'Dubai International', 'city' => 'Dubai', 'country' => 'United Arab Emirates', 'timezone' => 'Asia/Dubai', 'lat' => 25.2532, 'lng' => 55.3657],
            ['iata_code' => 'SIN', 'name' => 'Singapore Changi', 'city' => 'Singapore', 'country' => 'Singapore', 'timezone' => 'Asia/Singapore', 'lat' => 1.3644, 'lng' => 103.9915],
            ['iata_code' => 'HND', 'name' => 'Tokyo Haneda', 'city' => 'Tokyo', 'country' => 'Japan', 'timezone' => 'Asia/Tokyo', 'lat' => 35.5494, 'lng' => 139.7798],
            ['iata_code' => 'NRT', 'name' => 'Tokyo Narita', 'city' => 'Tokyo', 'country' => 'Japan', 'timezone' => 'Asia/Tokyo', 'lat' => 35.7647, 'lng' => 140.3864],
            ['iata_code' => 'CDG', 'name' => 'Paris Charles de Gaulle', 'city' => 'Paris', 'country' => 'France', 'timezone' => 'Europe/Paris', 'lat' => 49.0097, 'lng' => 2.5479],
            ['iata_code' => 'FRA', 'name' => 'Frankfurt am Main', 'city' => 'Frankfurt', 'country' => 'Germany', 'timezone' => 'Europe/Berlin', 'lat' => 50.0379, 'lng' => 8.5622],
            ['iata_code' => 'HKG', 'name' => 'Hong Kong International', 'city' => 'Hong Kong', 'country' => 'Hong Kong', 'timezone' => 'Asia/Hong_Kong', 'lat' => 22.3080, 'lng' => 113.9185],
            ['iata_code' => 'SYD', 'name' => 'Sydney Kingsford Smith', 'city' => 'Sydney', 'country' => 'Australia', 'timezone' => 'Australia/Sydney', 'lat' => -33.9399, 'lng' => 151.1753],
            ['iata_code' => 'ORD', 'name' => "Chicago O'Hare", 'city' => 'Chicago', 'country' => 'United States', 'timezone' => 'America/Chicago', 'lat' => 41.9742, 'lng' => -87.9073],
            ['iata_code' => 'DEL', 'name' => 'Indira Gandhi International', 'city' => 'Delhi', 'country' => 'India', 'timezone' => 'Asia/Kolkata', 'lat' => 28.5562, 'lng' => 77.1000],
        ];

        Airport::upsert($airports, ['iata_code']);
    }
}
