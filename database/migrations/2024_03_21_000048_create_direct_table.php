<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('oid')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('number', 100)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('town', 100)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('mob', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct');
    }
};
