<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driverDays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->nullable();
            $table->string('day', 100)->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->boolean('start_driving_exactly_from_the_shift_start')->default(0);
            $table->boolean('checked')->default(0);
            $table->string('start_day', 200)->nullable();
            $table->string('end_day', 200)->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driverDays');
    }
};
