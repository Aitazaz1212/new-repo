<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp', function (Blueprint $table) {
            $table->id();
            $table->string('name', 400)->nullable();
            $table->integer('sent')->nullable();
            $table->integer('not_sent')->nullable();
            $table->string('date', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp');
    }
}; 