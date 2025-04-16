<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin', function (Blueprint $table) {
            $table->id();
            $table->timestamp('expires')->nullable();
            $table->text('message')->nullable();
            $table->integer('staff_id')->nullable();
            $table->string('title')->nullable();
            $table->tinyInteger('deleted')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin');
    }
}; 