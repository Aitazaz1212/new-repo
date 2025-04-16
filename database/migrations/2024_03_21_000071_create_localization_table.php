<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localization', function (Blueprint $table) {
            $table->integer('id')->default(1)->primary();
            $table->string('currency', 200);
            $table->string('distance_units', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localization');
    }
}; 