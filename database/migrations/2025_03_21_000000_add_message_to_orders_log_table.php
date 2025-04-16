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
        Schema::table('orders_log', function (Blueprint $table) {
            // Check if message column doesn't exist before adding
            if (!Schema::hasColumn('orders_log', 'message')) {
                $table->string('message', 255)->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_log', function (Blueprint $table) {
            if (Schema::hasColumn('orders_log', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
