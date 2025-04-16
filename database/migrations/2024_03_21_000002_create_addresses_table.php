<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_customer')->nullable();
            $table->foreignId('id_address_type');
            $table->foreignId('id_address_status');
            $table->string('name', 128)->nullable();
            $table->string('address_line_1', 128)->nullable();
            $table->string('address_line_2', 128)->nullable();
            $table->string('address_line_3', 128)->nullable();
            $table->string('address_line_4', 128)->nullable();
            $table->string('address_line_5', 128)->nullable();
            $table->string('postal_reference', 20)->nullable();
            $table->string('country_code', 4)->nullable();
            $table->string('longitude', 32)->nullable();
            $table->string('latitude', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
}; 