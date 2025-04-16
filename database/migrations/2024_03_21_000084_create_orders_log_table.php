<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders_log', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->nullable();
            $table->string('proxy', 45)->nullable();
            $table->integer('oid')->nullable();
            $table->string('local', 45)->nullable();
            $table->integer('user_id')->nullable();
            $table->string('actions', 255)->nullable();
            $table->string('status', 255)->nullable();
            $table->string('eta', 255)->nullable();
            $table->datetime('timeAndDate');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders_log');
    }
}; 