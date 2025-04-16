<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territory_driver', function (Blueprint $table) {
            $table->id();
            $table->integer('territory_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->timestamps();

            // Add indexes for foreign keys and common queries
            $table->index(['territory_id', 'driver_id']);
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territorie_driver');
    }
}; 