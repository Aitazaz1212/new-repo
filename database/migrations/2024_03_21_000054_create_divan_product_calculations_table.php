<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divan_product_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->bigInteger('is_parent')->nullable()->comment('used for product type. 0 for parent');
            $table->json('configurable_variations')->nullable();
            $table->string('set_code')->nullable();
            $table->double('price', 8, 2)->nullable();
            $table->double('special_price', 8, 2)->nullable();
            $table->double('percentage', 8, 2)->nullable();
            $table->string('storage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divan_product_calculations');
    }
}; 