<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->datetime('changed_datetime')->nullable()->useCurrent();
            $table->string('from_status', 24)->nullable();
            $table->integer('order_id')->nullable();
            $table->text('text')->nullable();
            $table->string('to_status', 24)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
}; 