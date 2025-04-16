<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_tasks', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 45)->nullable();
            $table->string('description', 150)->nullable();
            $table->integer('value')->default(0)->nullable();
            $table->string('log', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_tasks');
    }
}; 