<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('imei', 400)->nullable();
            $table->string('tel', 20)->nullable();
            $table->integer('driver')->nullable();
            $table->text('token')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
}; 