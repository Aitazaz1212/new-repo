<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cons', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('consid', 20)->nullable();
            $table->string('deliverTo', 200)->nullable();
            $table->string('town', 200)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->integer('pieces')->nullable();
            $table->double('weight')->nullable();
            $table->string('type', 200)->default('Delivery')->nullable();
            $table->string('date', 40)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('stamp', 40)->nullable();
            $table->integer('to')->nullable();
            $table->integer('sender')->nullable();
            $table->integer('parcelid')->nullable();
            $table->double('price')->default(0)->nullable();
            $table->double('paid')->default(0)->nullable();
            $table->integer('oid')->nullable();
            $table->string('delDate', 20)->nullable();
            $table->integer('colID')->nullable();
            $table->integer('hide')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cons');
    }
}; 