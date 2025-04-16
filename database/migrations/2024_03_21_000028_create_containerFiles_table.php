<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containerFiles', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 200)->nullable();
            $table->string('containerID', 400)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containerFiles');
    }
}; 