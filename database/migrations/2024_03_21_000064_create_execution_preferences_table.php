<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('use_driver_manifest_template')->nullable();
            $table->string('prevent_order_completion_before_collecting_a_signature')->nullable();
            $table->string('prevent_order_completion_before_attaching_photo')->nullable();
            $table->string('attachments_upload_mode')->nullable();
            $table->string('display_order_priorities_on_the_mobile_app')->nullable();
            $table->string('enable_order_item_checkbox')->nullable();
            $table->string('enable_check_all_for_order_items')->nullable();
            $table->string('items_default_state_checked')->nullable();
            $table->string('display_price_info_on_the_mobile_app')->nullable();
            $table->string('area_radius_for_track_trace_control_meters')->nullable();
            $table->string('send_order_details_by_timer')->nullable();
            $table->string('send_order_details_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_preferences');
    }
};
