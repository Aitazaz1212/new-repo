<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_email_formats', function (Blueprint $table) {
            $table->id();
            $table->string('actions')->nullable();
            $table->string('delay_sending_duration_silent_hours')->nullable();
            $table->string('send_at')->nullable();
            $table->string('sms_messages')->nullable();
            $table->string('email_subject')->nullable();
            $table->string('email_messages')->nullable();
            $table->integer('sms')->nullable();
            $table->integer('email')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->integer('attachPod')->default(0);
            $table->string('fileName')->nullable();
            $table->integer('NumberOfDaysBeforePlannedArrival')->default(0);
            $table->time('sentAt')->default('00:00:00');
            $table->integer('NotificationBefore')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_email_formats');
    }
};
