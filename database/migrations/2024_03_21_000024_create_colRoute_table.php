<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colRoute', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('route', 100)->nullable();
            $table->string('date', 20)->nullable();
            $table->string('driver', 100)->nullable();
            $table->string('staff', 100)->nullable();
            $table->string('status', 100)->default('Routed')->nullable();
            $table->string('mate', 100)->nullable();
            $table->string('vehicle', 100)->nullable();
            $table->double('payments')->default(0)->nullable();
            $table->double('toCollect')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colRoute');
    }
}; 