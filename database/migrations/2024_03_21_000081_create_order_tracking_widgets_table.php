<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tracking_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widgetDomain', 22);
            $table->string('widgetLocation', 800);
            $table->integer('rateDelivery');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tracking_widgets');
    }
};
