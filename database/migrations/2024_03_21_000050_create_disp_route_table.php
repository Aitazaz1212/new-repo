<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispRoute', function (Blueprint $table) {
            $table->id();
            $table->string('date', 20)->nullable();
            $table->string('driver', 48)->nullable();
            $table->integer('driverPaid')->default(0)->nullable();
            $table->string('mate', 48)->nullable();
            $table->integer('matePaid')->default(0)->nullable();
            $table->string('picker', 100)->nullable();
            $table->double('payments')->default(0)->nullable();
            $table->string('route', 24)->nullable();
            $table->string('staff', 24)->nullable();
            $table->string('startTime', 20)->nullable();
            $table->string('status', 24)->default('Routed')->nullable();
            $table->double('toCollect')->default(0)->nullable();
            $table->string('vehicle', 24)->nullable();
            $table->integer('id_route')->nullable();
            $table->string('loader', 45)->nullable();
            $table->integer('driver_id')->nullable();
            $table->time('endTime')->nullable();
            $table->time('exp_delivery_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispRoute');
    }
}; 