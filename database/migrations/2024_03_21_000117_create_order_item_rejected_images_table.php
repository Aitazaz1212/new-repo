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
        Schema::create('order_item_rejected_images', function (Blueprint $table) {
            $table->id();
            $table->integer('order_item_id');
            $table->string('image')->nullable();
            $table->string('type', 50)->nullable();
            $table->timestamps();

            // Optional: Add foreign key constraint if you have the order_items table
            // $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_rejected_images');
    }
}; 