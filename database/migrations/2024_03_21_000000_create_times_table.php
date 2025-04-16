<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('times', function (Blueprint $table) {
            $table->id();
            $table->string('get_order_time', 100)->nullable();
            $table->string('backup_time', 100)->nullable();
            $table->string('get_active_order_time', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('times');
    }
}; 