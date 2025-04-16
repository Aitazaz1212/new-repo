<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('customerID');
            $table->unsignedInteger('orderID');
            $table->unsignedInteger('dispID')->nullable();
            $table->string('type');
            $table->boolean('hasSent');
            $table->boolean('cantsend')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}; 