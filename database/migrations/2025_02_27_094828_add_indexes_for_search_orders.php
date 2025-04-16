<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->indexExists('orders', 'orders_cid_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('cid');
            });
        }
        if (!$this->indexExists('orders', 'orders_ref_no_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('ref_no');
            });
        }
        if (!$this->indexExists('orders', 'orders_warehouse_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('warehouse_id');
            });
        }
        if (!$this->indexExists('orders', 'orders_orderStatus_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('orderStatus');
            });
        }
        if (!$this->indexExists('orders', 'orders_deliverBy_datetime_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('deliverBy_datetime');
            });
        }

        if (!$this->indexExists('customers', 'customers_businessName_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('businessName');
            });
        }

        if (!$this->indexExists('orderItems', 'orderItems_itemStatus_index')) {
            Schema::table('orderItems', function (Blueprint $table) {
                $table->index('itemStatus');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cid']);
            $table->dropIndex(['ref_no']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['orderStatus']);
            $table->dropIndex(['deliverBy_datetime']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['businessName']);
        });

        Schema::table('orderItems', function (Blueprint $table) {
            $table->dropIndex(['itemStatus']);
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
