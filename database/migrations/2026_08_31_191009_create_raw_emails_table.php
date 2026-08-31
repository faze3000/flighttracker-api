<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('external_message_id')->nullable();
            $table->string('status')->default('pending');
            $table->longText('raw_content')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'source', 'external_message_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_emails');
    }
};
