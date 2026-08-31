<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at')->nullable();

            // Mailbox watch/subscription, so an inbound push notification (which
            // carries the provider's own identifiers, not ours) can be matched
            // back to this connection's user.
            $table->string('provider_account_email')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->timestamp('watch_expiration')->nullable();

            // Gmail history.list cursor — the historyId our last successful
            // sync caught up to, so the next push only fetches what's new.
            $table->string('last_history_id')->nullable();

            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_connections');
    }
};
