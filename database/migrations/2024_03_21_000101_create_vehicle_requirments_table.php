<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('working_time_before_break')->nullable();
            $table->string('incompatible_vehicle_requirements')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index('ref_no');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_requirments');
    }
}; 