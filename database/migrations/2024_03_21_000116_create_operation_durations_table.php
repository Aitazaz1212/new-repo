<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operation_durations', function (Blueprint $table) {
            $table->id();
            $table->string('fixed_loading_duration')->nullable();
            $table->string('variable_loading_duration_per_unit')->nullable();
            $table->string('fixed_time_per_address')->nullable();
            $table->string('fixed_time_per_order')->nullable();
            $table->string('variable_time_per_capacity_delivery')->nullable();
            $table->string('variable_time_per_capacity_collection')->nullable();
            $table->string('fixed_un_loading_duration')->nullable();
            $table->string('variable_un_loading_duration_per_unit')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_durations');
    }
}; 