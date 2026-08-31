<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            ['iata_code' => 'BA', 'name' => 'British Airways', 'alliance' => 'oneworld'],
            ['iata_code' => 'AA', 'name' => 'American Airlines', 'alliance' => 'oneworld'],
            ['iata_code' => 'CX', 'name' => 'Cathay Pacific', 'alliance' => 'oneworld'],
            ['iata_code' => 'QF', 'name' => 'Qantas', 'alliance' => 'oneworld'],
            ['iata_code' => 'UA', 'name' => 'United Airlines', 'alliance' => 'Star Alliance'],
            ['iata_code' => 'LH', 'name' => 'Lufthansa', 'alliance' => 'Star Alliance'],
            ['iata_code' => 'SQ', 'name' => 'Singapore Airlines', 'alliance' => 'Star Alliance'],
            ['iata_code' => 'NH', 'name' => 'All Nippon Airways', 'alliance' => 'Star Alliance'],
            ['iata_code' => 'DL', 'name' => 'Delta Air Lines', 'alliance' => 'SkyTeam'],
            ['iata_code' => 'AF', 'name' => 'Air France', 'alliance' => 'SkyTeam'],
            ['iata_code' => 'KL', 'name' => 'KLM', 'alliance' => 'SkyTeam'],
            ['iata_code' => 'EK', 'name' => 'Emirates', 'alliance' => null],
            ['iata_code' => 'EY', 'name' => 'Etihad Airways', 'alliance' => null],
        ];

        Airline::upsert($airlines, ['iata_code']);
    }
}
