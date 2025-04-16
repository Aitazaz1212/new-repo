<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->integer('warehouse_id')->nullable();
            $table->integer('stock_parts_id')->nullable();
            $table->integer('quantity')->nullable();

            // Add indexes for foreign keys and common queries
            $table->index('warehouse_id');
            $table->index('stock_parts_id');
            $table->index(['warehouse_id', 'stock_parts_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock');
    }
}; 