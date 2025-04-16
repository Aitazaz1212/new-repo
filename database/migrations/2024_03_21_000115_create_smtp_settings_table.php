<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('email', 500)->nullable();
            $table->string('pwd', 500)->nullable();
            $table->string('smtp', 400)->nullable();
            $table->string('name', 100)->nullable();
            $table->foreignId('id_company')->nullable();
            $table->integer('tls')->default(0);
            $table->string('outName')->nullable();
            $table->string('ssl_one')->nullable();
            $table->string('port')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
