<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionPreference extends Model
{
    protected $fillable = [
        'use_driver_manifest_template',
        'prevent_order_completion_before_collecting_a_signature',
        'prevent_order_completion_before_attaching_photo',
        'attachments_upload_mode',
        'display_order_priorities_on_the_mobile_app',
        'enable_order_item_checkbox',
        'enable_check_all_for_order_items',
        'items_default_state_checked',
        'display_price_info_on_the_mobile_app',
        'area_radius_for_track_trace_control_meters',
        'send_order_details_by_timer',
        'send_order_details_at'
    ];

    // Constants for common values if needed
    public const UPLOAD_MODE_IMMEDIATE = 'immediate';
    public const UPLOAD_MODE_WIFI = 'wifi_only';
    public const UPLOAD_MODE_MANUAL = 'manual';
} 