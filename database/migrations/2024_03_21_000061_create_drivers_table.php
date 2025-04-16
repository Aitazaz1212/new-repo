<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('score')->nullable();
            $table->string('uName', 64)->nullable();
            $table->string('pwd')->nullable();
            $table->string('num', 24)->nullable();
            $table->decimal('rate', 6, 2)->default(65.00)->nullable();
            $table->integer('user_id')->nullable();
            $table->char('active', 1)->default('1')->nullable();
            $table->string('comment')->nullable();
            $table->string('external_id')->nullable();
            $table->integer('vehicle')->nullable();
            $table->string('cost_per_hour')->nullable();
            $table->string('alerts_for_performers')->nullable();
            $table->integer('distribution_centre')->nullable();
            $table->json('territories')->nullable();
            $table->text('start_of_day_location')->nullable();
            $table->text('end_of_day_location')->nullable();
            $table->string('driving_limit')->nullable();
            $table->string('duty_time_limit')->nullable();
            $table->string('run_duration_limit')->nullable();
            $table->text('start_of_day_address')->nullable();
            $table->text('end_of_day_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
}; 