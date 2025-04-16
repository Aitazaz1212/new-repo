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
        Schema::create('planning_tool_routes_cache', function (Blueprint $table) {
            $table->id();
            $table->string('route_id');
            $table->string('origin_lat');
            $table->string('origin_lng');
            $table->string('destination_lat');
            $table->string('destination_lng');
            $table->json('points');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_tool_routes_cache');
    }
};
