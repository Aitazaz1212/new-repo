<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->boolean('always_in_stock')->default(0)->nullable();
            $table->integer('id_staff')->nullable();
            $table->integer('id_supplier')->default(0)->nullable();
            $table->text('description')->nullable();
            $table->string('name', 200)->nullable();
            $table->string('staff', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
}; 