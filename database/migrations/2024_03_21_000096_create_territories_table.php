<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ref_no');
            $table->text('coordinates');
            $table->text('radius')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('color')->nullable();
            $table->string('group_id')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index('ref_no');
            $table->index('warehouse_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territories');
    }
}; 