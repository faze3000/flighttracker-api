<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_email_id')->nullable()->constrained('raw_emails')->nullOnDelete();

            $table->string('booking_reference')->nullable();

            $table->string('marketing_airline_iata', 2);
            $table->string('operating_airline_iata', 2)->nullable();
            $table->string('marketing_flight_number', 6);

            $table->string('departure_airport_iata', 3);
            $table->timestamp('departure_time_local');
            $table->string('departure_timezone');
            $table->string('departure_terminal')->nullable();
            $table->string('departure_gate')->nullable();

            $table->string('arrival_airport_iata', 3);
            $table->timestamp('arrival_time_local');
            $table->string('arrival_timezone');
            $table->string('arrival_terminal')->nullable();
            $table->string('arrival_gate')->nullable();

            $table->string('cabin_class')->nullable();
            $table->string('seat')->nullable();
            $table->string('aircraft_type')->nullable();
            $table->string('status')->default('confirmed');

            $table->decimal('price_amount', 10, 2)->nullable();
            $table->string('price_currency', 3)->nullable();

            $table->decimal('confidence_score', 4, 3)->nullable();
            $table->boolean('needs_review')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'departure_time_local']);
            $table->index('trip_id');
            $table->index('booking_reference');
            $table->index(['departure_airport_iata', 'arrival_airport_iata']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_segments');
    }
};
