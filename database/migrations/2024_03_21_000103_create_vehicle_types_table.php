<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('working_time_before_break')->nullable();
            $table->string('routing_mode')->nullable();
            $table->string('avoid_urban_areas')->nullable();
            $table->string('avoid_london_ultra_low_emission_zone')->nullable();
            $table->string('weight', 222)->nullable();
            $table->string('height', 222)->nullable();
            $table->string('width', 222)->nullable();
            $table->string('length', 222)->nullable();
            $table->string('axle_load', 222)->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index('ref_no');
            $table->index('name');
            $table->index('routing_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
}; 