<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->integer('oid');
            $table->integer('vehicle_id_previous')->nullable();
            $table->integer('vehicle_id_current')->nullable();
            $table->integer('id_disp')->nullable();
            $table->integer('id_disp_route')->nullable();
            $table->integer('action');
            $table->string('to')->nullable();
            $table->string('from')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
