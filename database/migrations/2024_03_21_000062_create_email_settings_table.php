<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emailSettings', function (Blueprint $table) {
            $table->id();
            $table->string('email', 500)->nullable();
            $table->string('pwd', 500)->nullable();
            $table->string('smtp', 400)->nullable();
            $table->string('name', 100)->nullable();
            $table->integer('id_company')->nullable();
            $table->integer('tls')->default(0)->nullable();
            $table->string('outName')->nullable();
            $table->string('ssl_one')->nullable();
            $table->string('port')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emailSettings');
    }
}; 