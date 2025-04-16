<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('name')->nullable();
            $table->string('location', 48)->nullable();
            $table->string('user_id')->nullable();
            $table->string('ref_number')->nullable();
            $table->string('address_prefix')->nullable();
            $table->string('phone')->nullable();
            $table->json('dispatcher')->nullable();
            $table->string('driving_time_correction_factor')->nullable();
            $table->string('daily_driving_limt')->nullable();
            $table->string('duty_time_limt')->nullable();
            $table->string('run_duration_limt')->nullable();
            $table->string('collection_after_deliveries')->nullable();
            $table->string('start_fo_day_location')->nullable();
            $table->string('end_of_day_location')->nullable();
            $table->json('sunday')->nullable();
            $table->json('monday')->nullable();
            $table->json('tuesday')->nullable();
            $table->json('wednesday')->nullable();
            $table->json('thursday')->nullable();
            $table->json('friday')->nullable();
            $table->json('saturday')->nullable();
            $table->json('holidays')->nullable();
            $table->string('latitude', 200)->nullable();
            $table->string('longitude', 200)->nullable();
            $table->string('postalCode', 200)->nullable();
            $table->text('name_of_warehouse')->nullable();
            $table->text('location_of_warehouse')->nullable();
            $table->integer('checked')->default(0);
            $table->text('visitDistributionCenter')->nullable();
            $table->text('vistDistributionLocation')->nullable();

            // Add indexes for common queries
            $table->index('ref_number');
            $table->index('user_id');
            $table->index(['latitude', 'longitude']);
            $table->index('postalCode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
}; 