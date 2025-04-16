<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinates', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('pc', 10)->nullable();
            $table->string('lat', 30)->nullable();
            $table->string('lng', 30)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('town', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinates');
    }
}; 