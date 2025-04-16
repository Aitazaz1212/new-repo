<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('loaded', 20)->nullable();
            $table->string('eta', 20)->nullable();
            $table->string('containerID', 400)->nullable();
            $table->integer('rxd')->default(0)->nullable();
            $table->text('items')->nullable();
            $table->integer('staff_id')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
}; 