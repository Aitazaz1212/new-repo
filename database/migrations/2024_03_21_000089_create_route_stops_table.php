<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routeStops', function (Blueprint $table) {
            $table->id();
            $table->integer('routeID')->nullable();
            $table->string('postCode', 20)->nullable();
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('routeID');
            $table->index('postCode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routeStops');
    }
}; 