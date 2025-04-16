<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_companies', function (Blueprint $table) {
            $table->id();
            $table->string('global_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_logo')->nullable();
            $table->string('street_address')->nullable();
            $table->string('street_address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postcode')->nullable();
            $table->string('ph')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('contact_position')->nullable();
            $table->json('contact_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_companies');
    }
}; 