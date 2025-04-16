<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        if (!$this->indexExists('orders', 'orders_max_parent_order_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('max_parent_order_id');
            });
        }

        if (!$this->indexExists('disp', 'disp_oid_index')) {
            Schema::table('disp', function (Blueprint $table) {
                $table->index('oid');
            });
        }

        if (!$this->indexExists('disp', 'disp_num_index')) {
            Schema::table('disp', function (Blueprint $table) {
                $table->index('num');
            });
        }

        if (!$this->indexExists('driverDays', 'driverDays_day_index')) {
            Schema::table('driverDays', function (Blueprint $table) {
                $table->index('day');
            });
        }

        if (!$this->indexExists('driver_locations', 'driver_locations_route_id_index')) {
            Schema::table('driver_locations', function (Blueprint $table) {
                $table->index('route_id');
            });
        }

        if (!$this->indexExists('vehicles', 'vehicles_distribution_centre_id_index')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index('distribution_centre_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['max_parent_order_id']);
        });

        Schema::table('disp', function (Blueprint $table) {
            $table->dropIndex(['oid']);
            $table->dropIndex(['num']);
        });

        Schema::table('driverDays', function (Blueprint $table) {
            $table->dropIndex(['day']);
        });

        Schema::table('driver_locations', function (Blueprint $table) {
            $table->dropIndex(['route_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['distribution_centre_id']);
        });
    }

    /**
     * Check if an index exists in a given table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = env('DB_DATABASE');
        $result = DB::select("SELECT COUNT(1) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?", [$database, $table, $indexName]);

        return !empty($result) && $result[0]->count > 0;
    }
};
