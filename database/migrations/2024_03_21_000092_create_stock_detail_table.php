<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockDetail', function (Blueprint $table) {
            $table->id();
            $table->integer('sid')->nullable();
            $table->double('weight')->nullable();
            $table->double('cbm')->nullable();
            $table->float('length')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->boolean('two_man_lift')->nullable();
            $table->boolean('greater_than_1m')->nullable();
            $table->boolean('bulky')->nullable();
            $table->timestamps();

            // Add index for faster lookups
            $table->index('sid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockDetail');
    }
}; 