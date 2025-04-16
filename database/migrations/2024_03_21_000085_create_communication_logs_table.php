<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('user_id');
            $table->string('type', 50); // EMAIL_SENT, SMS_SENT, etc.
            $table->string('recipient');
            $table->text('content');
            $table->string('status', 50)->default('sent');
            $table->string('ip', 45)->nullable();
            $table->string('proxy', 45)->nullable();
            $table->string('local', 45)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
