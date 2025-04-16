<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->json('supported_vehicle_requirements')->nullable();
            $table->string('vehicle_type', 200)->nullable();
            $table->integer('max_speed')->nullable();
            $table->integer('driving_time_correction_factor')->nullable();
            $table->integer('cost_per_mile')->nullable();
            $table->integer('vehicle_activation_cost')->nullable();
            $table->integer('cost_per_order')->nullable();
            $table->integer('capacityWeight')->nullable();
            $table->time('driving_time')->nullable();
            $table->time('Run_duration_limit')->nullable();
            $table->time('duty_time_limit')->nullable();
            $table->tinyInteger('automatic_break_shift')->nullable();
            $table->integer('cost_per_hour')->nullable();
            $table->integer('run_distance_limit')->nullable();
            $table->date('forDate')->nullable();
            $table->time('time')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('vehicle_type');
            $table->index('forDate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_settings');
    }
}; 