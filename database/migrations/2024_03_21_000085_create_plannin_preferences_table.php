<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('enable_toll_roads')->default('no');
            $table->string('allow_several_runs_for_a_vehicle_per_day')->default('no');
            $table->string('territories_planning_mode')->nullable();
            $table->string('working_time_before_break')->nullable();
            $table->string('enable_on_board_time_limit_for_deliveries')->default('no');
            $table->string('on_board_time_limit_for_deliveries')->nullable();
            $table->string('enable_on_board_time_limit_for_collections')->default('no');
            $table->string('on_board_time_limit_for_collections')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plannin_preferences');
    }
}; 