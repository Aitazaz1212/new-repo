<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes', function (Blueprint $table) {
            $table->integer('pk')->primary();
            $table->integer('lineid')->nullable();
            $table->string('bar', 48)->nullable();
            $table->integer('oid')->nullable();
            $table->integer('parcelid')->nullable();
            $table->string('itemCode', 128)->nullable();
            $table->integer('loaded')->default(0)->nullable();
            $table->integer('warehouse')->default(0)->nullable();
            $table->integer('complete')->default(0)->nullable();
            $table->integer('collected')->default(0)->nullable();
            $table->integer('boxes')->nullable()->comment('for boxes logics');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes');
    }
}; 