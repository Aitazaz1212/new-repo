<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id()->comment('20.01 changed to int(11)');
            $table->double('cost')->default(0)->nullable();
            $table->string('itemCode', 255)->nullable();
            $table->string('itemName', 100)->nullable();
            $table->text('itemDescription')->nullable()->comment('20.01 changed to text');
            $table->tinyInteger('id_company')->nullable();
            $table->tinyInteger('sms')->default(1);
            $table->integer('itemQty')->nullable();
            $table->integer('itemAlloc')->nullable();
            $table->integer('itemOnOrder')->nullable();
            $table->boolean('needs_attention')->default(true)->nullable();
            $table->double('retail')->default(0)->nullable();
            $table->double('wholesale')->default(0)->nullable();
            $table->decimal('weight', 11, 3)->default(0.000)->nullable();
            $table->string('dimensions', 100)->default('0')->nullable();
            $table->string('bin', 100)->nullable();
            $table->integer('actual')->default(0)->nullable()->comment('20.01 changed to int(11)');
            $table->integer('pieces')->default(1)->nullable();
            $table->string('isMulti', 24)->default('1')->nullable()->comment('the number of boxes for the item');
            $table->integer('warehouse')->nullable();
            $table->integer('label')->default(1)->nullable();
            $table->integer('blocked')->default(1)->nullable();
            $table->integer('seller')->default(0)->nullable();
            $table->string('cat', 100)->default('')->nullable();
            $table->integer('qty_in_stock')->default(0)->nullable();
            $table->integer('qty_on_so')->default(0)->nullable();
            $table->integer('qty_ordered')->default(0)->nullable();
            $table->integer('category_id')->default(0)->nullable();
            $table->double('beds_price')->default(0)->nullable();
            $table->double('ebay_price')->default(0)->nullable();
            $table->integer('route_id')->default(0)->nullable();
            $table->string('colour', 24)->nullable();
            $table->decimal('gross_weight', 6, 2)->nullable();
            $table->text('manifest_description')->nullable();
            $table->string('model_no', 24)->nullable();
            $table->decimal('net_weight', 6, 2)->nullable();
            $table->double('cbm')->nullable();
            $table->tinyInteger('two_man_lift')->nullable();
            $table->tinyInteger('greater_than_1m')->nullable();
            $table->double('parts')->nullable();
            $table->double('bulky')->nullable();
            $table->double('thickness')->nullable();
            $table->double('cube')->nullable();
            $table->double('height')->nullable();
            $table->double('length')->nullable();
            $table->double('width')->nullable();
            $table->string('product_size', 196)->nullable();
            $table->integer('stock_category_id')->nullable();
            $table->boolean('withdrawn')->default(false)->nullable();
            $table->boolean('always_in_stock')->default(false)->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();

            // Add indexes for common queries
            $table->index('itemCode');
            $table->index('category_id');
            $table->index('stock_category_id');
            $table->index('isActive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
}; 