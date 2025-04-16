<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowed_ips', function (Blueprint $table) {
            $table->id();
            $table->string('address', 48)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_ips');
    }
}; 