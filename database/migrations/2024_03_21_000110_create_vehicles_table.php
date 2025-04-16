<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('reg', 30)->nullable();
            $table->decimal('height', 10, 2)->default(0.00)->nullable();
            $table->decimal('width', 10, 2)->default(0.00)->nullable();
            $table->decimal('depth', 10, 2)->default(0.00)->nullable();
            $table->decimal('weight', 10, 2)->default(0.00)->nullable();
            $table->text('description')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('assigned_device')->nullable();
            $table->string('tcp_source')->nullable();
            $table->string('supported_vehicle')->nullable();
            $table->string('max_speed')->nullable();
            $table->string('driving_time_correction_factor')->nullable();
            $table->string('cost_per_mile')->nullable();
            $table->string('vehicle_activation_cost')->nullable();
            $table->string('cost_per_order')->nullable();
            $table->string('capacity_weight')->nullable();
            $table->string('run_distance_limit')->nullable();
            $table->integer('distribution_centre_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('territories')->nullable();
            $table->string('comment')->nullable();
            $table->string('manufacturer_info')->nullable();
            $table->string('vin')->nullable();
            $table->string('stand_down')->nullable();
            $table->string('archived')->nullable();
            $table->string('color')->nullable();
            $table->string('id_route', 20)->nullable();
            $table->float('volume')->nullable();

            // Add indexes for common queries
            $table->index('reg');
            $table->index('user_id');
            $table->index('driver_id');
            $table->index('distribution_centre_id');
            $table->index('vehicle_type');
            $table->index('external_id');
            $table->index('vin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
}; 