<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driverLocs', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id')->nullable();
            $table->double('lat')->default(0)->nullable();
            $table->double('lng')->default(0)->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driverLocs');
    }
}; 