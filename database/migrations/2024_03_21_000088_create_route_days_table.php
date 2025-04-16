<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routeDays', function (Blueprint $table) {
            $table->integer('routeDaysID')->primary();
            $table->string('route', 48)->nullable();
            $table->string('day', 24)->nullable();
            $table->integer('max')->default(30)->nullable();
            $table->integer('route_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routeDays');
    }
}; 