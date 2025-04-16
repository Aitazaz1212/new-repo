<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_errors', function (Blueprint $table) {
            $table->id();
            $table->string('note', 200)->nullable();
            $table->string('item', 45)->nullable();
            $table->integer('oid')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Add index for oid
            $table->index('oid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_errors');
    }
}; 