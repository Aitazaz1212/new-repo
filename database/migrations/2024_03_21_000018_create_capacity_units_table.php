<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_units', function (Blueprint $table) {
            $table->id();
            $table->string('capacity_unit')->nullable();
            $table->string('capacity_decimal_precision')->nullable();
            $table->string('capacity_label')->nullable();
            $table->string('volume_units')->nullable();
            $table->string('volume_label')->nullable();
            $table->string('volume_decimal_precision')->nullable();
            $table->boolean('enable_capacity_constraint_one')->nullable();
            $table->boolean('enable_capacity_constraint_two')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_units');
    }
}; 