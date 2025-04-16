<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('col', function (Blueprint $table) {
            $table->id();
            $table->integer('oid')->nullable();
            $table->string('date', 20)->nullable();
            $table->string('route', 100)->nullable();
            $table->integer('num')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('col');
    }
}; 