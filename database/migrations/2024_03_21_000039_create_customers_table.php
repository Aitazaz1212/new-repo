<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('accountNo', 100)->nullable();
            $table->integer('agent')->default(0)->nullable();
            $table->string('businessName')->nullable();
            $table->string('email1')->nullable();
            $table->string('email2')->nullable();
            $table->string('email3')->nullable();
            $table->text('fax')->nullable();
            $table->text('mob')->nullable();
            $table->text('number')->nullable();
            $table->string('web', 200)->nullable();
            $table->string('street')->nullable();
            $table->string('tel', 100)->nullable();
            $table->string('town')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('dBusinessName')->nullable();
            $table->string('dNumber')->nullable();
            $table->string('dStreet')->nullable();
            $table->string('dTown')->nullable();
            $table->string('dPostcode', 20)->nullable();
            $table->double('discount')->default(0)->nullable();
            $table->integer('id_customer_type')->nullable();
            $table->integer('id_seller')->nullable();
            $table->string('isAccount', 11)->default('0')->nullable();
            $table->string('paymentType', 200)->nullable();
            $table->string('type', 20)->nullable();
            $table->double('owes')->default(0)->nullable();
            $table->string('userID', 200)->nullable();
            $table->string('company')->default('0')->nullable();
            $table->integer('mail')->default(1)->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->bigInteger('numerical_pc')->default(0)->nullable();
            $table->string('rate', 11)->nullable();
            $table->integer('vehicle_requirements')->nullable();
            $table->json('preferred_drivers')->nullable();
            $table->string('description', 200)->nullable();
            $table->string('location_refrence', 200)->nullable();
            $table->string('verified_by', 33)->nullable();
            $table->boolean('notificationByEmail')->default(0);
            $table->boolean('notificationBySms')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
}; 