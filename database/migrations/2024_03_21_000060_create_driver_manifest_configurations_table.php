<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_manifest_configurations', function (Blueprint $table) {
            $table->id();
            $table->integer('task')->nullable();
            $table->integer('order_reference')->nullable();
            $table->integer('location_name')->nullable();
            $table->integer('location_address')->nullable();
            $table->integer('contact_number')->nullable();
            $table->integer('second_contact')->nullable();
            $table->integer('additional_instructions')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('stop_time')->nullable();
            $table->integer('origin_window')->nullable();
            $table->integer('client_name')->nullable();
            $table->integer('service_level')->nullable();
            $table->integer('customer_signature')->nullable();
            $table->integer('location_postcode')->nullable();
            $table->integer('contact_person')->nullable();
            $table->integer('priority')->nullable();
            $table->integer('order_item')->nullable();
            $table->integer('volume')->nullable();
            $table->integer('web_ref')->nullable();
            $table->integer('vehicle_requirements')->nullable();
            $table->integer('location_instructions')->nullable();
            $table->integer('area_of_control')->nullable();
            $table->integer('distance')->nullable();
            $table->integer('territory')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_manifest_configurations');
    }
}; 