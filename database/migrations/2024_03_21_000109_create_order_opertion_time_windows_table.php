<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_opertion_time_windows', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->nullable();
            $table->string('date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index('order_id');
            $table->index('date');
            $table->index(['order_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_opertion_time_windows');
    }
}; 