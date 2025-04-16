<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('count', function (Blueprint $table) {
            $table->id();
            $table->integer('num')->nullable();
            $table->integer('parcel_id_seed')->nullable();
            $table->integer('orders_count')->nullable();
            $table->integer('refund')->nullable();
            $table->integer('post')->nullable();
            $table->integer('exchange')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('count');
    }
};
