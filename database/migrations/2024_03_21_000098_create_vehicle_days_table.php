<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicleDays', function (Blueprint $table) {
            $table->string('reg', 200)->nullable();
            $table->string('day', 100)->nullable();
            
            // Add indexes for faster lookups
            $table->index('reg');
            $table->index('day');
            
            // Add composite index for common queries
            $table->index(['reg', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicleDays');
    }
}; 