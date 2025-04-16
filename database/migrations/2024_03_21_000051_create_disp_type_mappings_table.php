<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disp_type_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->integer('disptype_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disp_type_mappings');
    }
}; 