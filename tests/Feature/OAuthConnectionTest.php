<?php

namespace Tests\Feature;

use App\Enums\OAuthProvider;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gmail.client_id' => 'test-client-id',
            'services.gmail.client_secret' => 'test-client-secret',
            'services.gmail.redirect' => 'http://localhost/callback',
            'services.gmail.pubsub_topic' => 'projects/test/topics/gmail',
        ]);
    }

    public function test_connect_returns_an_authorization_url_and_stores_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/oauth/gmail/connect');

        $response->assertOk()->assertJsonStructure(['url']);
        $this->assertStringContainsString('accounts.google.com', $response->json('url'));
    }

    public function test_an_invalid_provider_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/auth/oauth/yahoo/connect')->assertNotFound();
    }

    public function test_the_callback_exchanges_the_code_and_stores_the_connection(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token-value',
                'refresh_token' => 'refresh-token-value',
                'expires_in' => 3600,
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/profile*' => Http::response(['emailAddress' => 'user@gmail.com']),
            'https://gmail.googleapis.com/gmail/v1/users/me/watch' => Http::response(['historyId' => '123', 'expiration' => (string) (now()->addDays(7)->timestamp * 1000)]),
        ]);

        Cache::put('oauth-connect-state:test-state', ['user_id' => $user->id, 'provider' => 'gmail'], now()->addMinutes(10));

        $response = $this->getJson('/api/auth/oauth/gmail/callback?state=test-state&code=auth-code');

        $response->assertOk()->assertJsonPath('status', 'connected');
        $this->assertDatabaseHas('oauth_connections', [
            'user_id' => $user->id,
            'provider' => OAuthProvider::Gmail->value,
            'provider_account_email' => 'user@gmail.com',
        ]);
    }

    public function test_disconnect_removes_the_connection(): void
    {
        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->for($user)->create(['provider' => OAuthProvider::Gmail]);

        Http::fake(['*' => Http::response([], 200)]);

        $this->actingAs($user)->deleteJson('/api/auth/oauth/gmail')->assertNoContent();

        $this->assertDatabaseMissing('oauth_connections', ['id' => $connection->id]);
    }
}
