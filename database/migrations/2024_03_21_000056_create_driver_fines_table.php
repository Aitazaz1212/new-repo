<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driverFines', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 100)->nullable();
            $table->double('amount')->nullable();
            $table->integer('paid')->default(0)->nullable();
            $table->string('date', 20)->nullable();
            $table->string('staff', 100)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driverFines');
    }
}; 