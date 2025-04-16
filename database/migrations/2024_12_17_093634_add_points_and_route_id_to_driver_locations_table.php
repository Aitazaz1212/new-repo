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
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->json('points')->after('lng');
            $table->unsignedBigInteger('route_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->dropColumn(['points' ,'route_id']);
        });
    }
};
