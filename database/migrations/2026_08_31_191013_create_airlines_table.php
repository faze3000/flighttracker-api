<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->string('iata_code', 2)->primary();
            $table->string('name');
            $table->string('alliance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airlines');
    }
};
