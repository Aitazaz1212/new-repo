<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_costs', function (Blueprint $table) {
            $table->id();
            $table->decimal('cost', 11, 2)->nullable();
            $table->date('date_incurred')->nullable();
            $table->integer('mileage')->nullable();
            $table->text('notes')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('vehicle_cost_type_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->timestamps();

            // Add indexes for foreign keys and common queries
            $table->index('vehicle_id');
            $table->index('vehicle_cost_type_id');
            $table->index('user_id');
            $table->index('date_incurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_costs');
    }
}; 