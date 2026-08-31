<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference data only — no data source decided yet (spec §6/§11.4).
        // Table exists so GET /lounges?airport={iata} has something to query
        // against once a source (Priority Pass-style feed or similar) is chosen.
        Schema::create('lounges', function (Blueprint $table) {
            $table->id();
            $table->string('airport_iata', 3);
            $table->string('name');
            $table->string('terminal')->nullable();
            $table->text('access_conditions')->nullable();
            $table->timestamps();

            $table->index('airport_iata');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lounges');
    }
};
