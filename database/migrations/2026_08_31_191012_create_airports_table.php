<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->string('iata_code', 3)->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country');
            $table->string('timezone');
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
