<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mates', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 100)->nullable();
            $table->string('num', 24)->nullable();
            $table->decimal('rate', 8, 2)->default(0.00)->nullable();
            $table->integer('matePaid')->default(0)->nullable();
            $table->integer('active')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mates');
    }
}; 