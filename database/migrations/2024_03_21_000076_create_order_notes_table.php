<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orderNotes', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('oid')->nullable();
            $table->string('date', 40)->nullable();
            $table->string('time', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('staff', 100)->nullable();
            $table->integer('driver')->default(0)->nullable();
            $table->boolean('pic')->default(0)->nullable();
            $table->integer('id_staff')->nullable();
            $table->integer('id_driver')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orderNotes');
    }
}; 