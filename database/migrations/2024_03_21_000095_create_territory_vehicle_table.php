<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territory_vehicle', function (Blueprint $table) {
            $table->id();
            $table->integer('territory_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->timestamps();

            // Add indexes for foreign keys
            $table->index(['territory_id', 'vehicle_id']);
            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_vehicle');
    }
}; 