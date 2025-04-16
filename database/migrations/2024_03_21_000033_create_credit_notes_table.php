<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->decimal('amount', 11, 2);
            $table->integer('order_id');
            $table->integer('seller_id');
            $table->integer('user_id');
            $table->decimal('used', 11, 2)->default(0.00)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
}; 