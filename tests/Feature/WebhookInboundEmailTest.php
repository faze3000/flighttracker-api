<?php

namespace Tests\Feature;

use App\Models\ForwardingAddress;
use App\Models\User;
use App\Services\Queue\RedisJsonQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookInboundEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.inbound_email.webhook_secret' => 'test-webhook-secret']);
    }

    private function sign(string $body): string
    {
        return hash_hmac('sha256', $body, 'test-webhook-secret');
    }

    public function test_a_correctly_signed_email_is_ingested_and_queued(): void
    {
        $this->mock(RedisJsonQueue::class, function ($mock) {
            $mock->shouldReceive('push')->once();
        });

        $user = User::factory()->create();
        $address = ForwardingAddress::create(['user_id' => $user->id, 'address' => 'abc123@fwd.flighttracker.app']);

        $payload = [
            'to' => $address->address,
            'message_id' => 'msg-1',
            'raw_mime' => "Subject: Booking confirmed\n\nDetails...",
        ];
        $body = json_encode($payload);

        $response = $this->call(
            'POST',
            '/webhooks/inbound-email',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Webhook-Signature' => $this->sign($body)],
            $body,
        );

        $response->assertAccepted();
        $this->assertDatabaseHas('raw_emails', ['user_id' => $user->id, 'external_message_id' => 'msg-1']);
    }

    public function test_an_incorrectly_signed_email_is_rejected(): void
    {
        $payload = ['to' => 'someone@fwd.flighttracker.app', 'message_id' => 'msg-2', 'raw_mime' => 'x'];
        $body = json_encode($payload);

        $response = $this->call(
            'POST',
            '/webhooks/inbound-email',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Webhook-Signature' => 'not-the-right-signature'],
            $body,
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('raw_emails', 0);
    }

    public function test_an_unknown_forwarding_address_is_rejected(): void
    {
        $payload = ['to' => 'nobody@fwd.flighttracker.app', 'message_id' => 'msg-3', 'raw_mime' => 'x'];
        $body = json_encode($payload);

        $response = $this->call(
            'POST',
            '/webhooks/inbound-email',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Webhook-Signature' => $this->sign($body)],
            $body,
        );

        $response->assertNotFound();
    }
}
