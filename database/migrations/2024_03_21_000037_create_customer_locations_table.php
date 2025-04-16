<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_locations', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('customer_location_reference')->nullable();
            $table->string('description')->nullable();
            $table->string('client_name')->nullable();
            $table->string('primary_telephone_number')->nullable();
            $table->string('secondary_telephone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('rate')->nullable();
            $table->string('address')->nullable();
            $table->string('postcode')->nullable();
            $table->string('run_distance_limit')->nullable();
            $table->string('location_is_verified')->nullable();
            $table->string('allow_notifications_by')->nullable();
            $table->string('preferred_drivers')->nullable();
            $table->string('vehicle_requirements')->nullable();
            $table->string('fixed_time_per_address')->nullable();
            $table->string('fixed_time_per_order')->nullable();
            $table->string('variable_time_per_capacity_delivery')->nullable();
            $table->string('variable_time_per_capacity_collection')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_locations');
    }
}; 