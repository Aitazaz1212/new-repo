<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->string('device_id');
            $table->string('fcm_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_fcm_tokens');
    }
}; 