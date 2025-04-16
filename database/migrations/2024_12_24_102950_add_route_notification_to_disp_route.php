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
        Schema::table('dispRoute', function (Blueprint $table) {
            $table->boolean('route_notification')->default(false); // Add the boolean column with a default val
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispRoute', function (Blueprint $table) {
            $table->dropColumn('route_notification'); // Remove the column if the migration is rolled back

        });
    }
};
