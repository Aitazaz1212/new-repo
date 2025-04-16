<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->string('break_type')->nullable();
            $table->string('break_duration')->nullable();
            $table->string('driving_time_before_break')->nullable();
            $table->string('working_time_before_break')->nullable();
            $table->string('allowed_break_shift')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaks');
    }
}; 