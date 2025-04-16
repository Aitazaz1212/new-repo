<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_delivery_package', function (Blueprint $table) {
            $table->id();
            $table->string('oid');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('sub_package_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->boolean('paid')->default(0);
            $table->string('stripe_payment_id')->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->string('payment_status')->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_delivery_package');
    }
}; 