<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('sender', 100)->nullable();
            $table->string('rcvr', 100)->nullable();
            $table->string('date', 100)->nullable();
            $table->string('msg', 1000)->nullable();
            $table->integer('seen')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
}; 