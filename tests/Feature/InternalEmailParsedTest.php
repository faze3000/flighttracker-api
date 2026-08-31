<?php

namespace Tests\Feature;

use App\Models\RawEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalEmailParsedTest extends TestCase
{
    use RefreshDatabase;

    private array $validSegment = [
        'marketing_airline_iata' => 'BA',
        'marketing_flight_number' => '117',
        'departure_airport_iata' => 'LHR',
        'departure_time_local' => '2026-09-01T10:00:00',
        'departure_timezone' => 'Europe/London',
        'arrival_airport_iata' => 'JFK',
        'arrival_time_local' => '2026-09-01T18:00:00',
        'arrival_timezone' => 'America/New_York',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_api.key' => 'test-internal-key']);
    }

    public function test_the_endpoint_rejects_requests_without_the_internal_key(): void
    {
        $email = RawEmail::factory()->create();

        $this->patchJson("/internal/emails/{$email->id}/parsed", [
            'relevant' => true,
            'confidence' => 0.9,
            'segments' => [$this->validSegment],
        ])->assertForbidden();
    }

    public function test_a_relevant_extraction_creates_a_flight_segment_and_a_trip(): void
    {
        $email = RawEmail::factory()->create();

        $response = $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", [
                'relevant' => true,
                'confidence' => 0.95,
                'segments' => [$this->validSegment],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('flight_segments', [
            'source_email_id' => $email->id,
            'marketing_flight_number' => '117',
            'needs_review' => false,
        ]);
        $this->assertDatabaseHas('raw_emails', ['id' => $email->id, 'status' => 'parsed']);
        $this->assertDatabaseCount('trips', 1);
    }

    public function test_a_low_confidence_extraction_is_flagged_for_review(): void
    {
        $email = RawEmail::factory()->create();

        $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", [
                'relevant' => true,
                'confidence' => 0.4,
                'segments' => [$this->validSegment],
            ])->assertOk();

        $this->assertDatabaseHas('flight_segments', [
            'source_email_id' => $email->id,
            'needs_review' => true,
        ]);
    }

    public function test_an_irrelevant_email_is_marked_irrelevant_without_creating_segments(): void
    {
        $email = RawEmail::factory()->create();

        $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", ['relevant' => false])
            ->assertOk();

        $this->assertDatabaseHas('raw_emails', ['id' => $email->id, 'status' => 'irrelevant']);
        $this->assertDatabaseCount('flight_segments', 0);
    }

    public function test_reprocessing_an_already_parsed_email_does_not_duplicate_segments(): void
    {
        $email = RawEmail::factory()->create();
        $payload = ['relevant' => true, 'confidence' => 0.95, 'segments' => [$this->validSegment]];

        $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", $payload)->assertOk();

        $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", $payload)->assertOk();

        $this->assertDatabaseCount('flight_segments', 1);
    }

    public function test_two_segments_sharing_a_booking_reference_land_in_the_same_trip(): void
    {
        $email = RawEmail::factory()->create();
        $outbound = [...$this->validSegment, 'booking_reference' => 'ABC123'];
        $inbound = [
            ...$this->validSegment,
            'booking_reference' => 'ABC123',
            'departure_airport_iata' => 'JFK',
            'arrival_airport_iata' => 'LHR',
            'departure_time_local' => '2026-09-08T10:00:00',
            'arrival_time_local' => '2026-09-08T22:00:00',
        ];

        $this->withHeader('X-Internal-Api-Key', 'test-internal-key')
            ->patchJson("/internal/emails/{$email->id}/parsed", [
                'relevant' => true,
                'confidence' => 0.9,
                'segments' => [$outbound, $inbound],
            ])->assertOk();

        $this->assertDatabaseCount('trips', 1);
        $this->assertDatabaseCount('flight_segments', 2);
    }
}
