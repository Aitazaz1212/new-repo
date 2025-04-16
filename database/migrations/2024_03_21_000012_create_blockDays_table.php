<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockDays', function (Blueprint $table) {
            $table->id();
            $table->string('date', 100)->nullable();
            $table->date('saturday')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockDays');
    }
}; 