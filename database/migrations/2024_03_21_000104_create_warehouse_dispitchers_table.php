<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_dispatchers', function (Blueprint $table) {
            $table->id();
            $table->integer('dispatcher_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->timestamps();

            // Add indexes for foreign keys
            $table->index(['dispatcher_id', 'warehouse_id']);
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_dispatchers');
    }
}; 