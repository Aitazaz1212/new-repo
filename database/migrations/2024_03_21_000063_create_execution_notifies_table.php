<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_notifies', function (Blueprint $table) {
            $table->id();
            $table->string('en_dispat_noti_before_the_driver_completes_the_run')->nullable();
            $table->string('send_e_mail_notification_before_x_not_started_orders')->nullable();
            $table->string('en_driver_noti_when_order_det_are_sent_to_mobile_dev')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_notifies');
    }
}; 