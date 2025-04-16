<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disp', function (Blueprint $table) {
            $table->id('id_disp');
            $table->integer('oid')->nullable();
            $table->string('date', 20)->nullable();
            $table->string('route', 48)->nullable();
            $table->integer('num')->default(0)->nullable();
            $table->integer('van')->default(1)->nullable();
            $table->integer('id_route')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disp');
    }
}; 