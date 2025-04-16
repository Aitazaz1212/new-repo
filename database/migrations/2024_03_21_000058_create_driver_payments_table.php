<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driverPayments', function (Blueprint $table) {
            $table->id(); // Adding primary key as per Laravel requirements
            $table->string('dates', 600)->nullable();
            $table->double('amount')->nullable();
            $table->string('date', 20)->nullable();
            $table->string('driver', 200)->nullable();
            $table->string('staff', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driverPayments');
    }
}; 