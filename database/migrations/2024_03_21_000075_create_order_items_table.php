<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orderItems', function (Blueprint $table) {
            $table->id()->comment('20.01 changed to int(11)');
            $table->integer('id_staff')->nullable();
            $table->integer('id_item_status')->nullable();
            $table->integer('itemid')->nullable();
            $table->integer('oid')->nullable();
            $table->text('bedsName')->nullable();
            $table->integer('blocked')->default(0)->nullable();
            $table->integer('currStock')->nullable()->comment('changed to int(6)');
            $table->double('discount')->default(0)->nullable();
            $table->string('itemStatus', 200)->nullable();
            $table->double('lineTotal')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->integer('Qty')->nullable();
            $table->string('staffName', 100)->nullable();
            $table->double('costs')->default(0)->nullable();
            $table->string('barcode', 200)->nullable();
            $table->string('item_ex_id', 200)->nullable();
            $table->integer('item_actual_quantity')->nullable();
            $table->string('status', 200)->nullable();
            $table->boolean('is_rejected')->default(0)->nullable();
            $table->string('rejected_reason')->nullable();
            $table->string('comments')->nullable();
            $table->smallInteger('qty_rejected')->default(0)->nullable();
            $table->smallInteger('qty_scanned')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orderItems');
    }
}; 