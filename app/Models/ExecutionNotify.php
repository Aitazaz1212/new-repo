<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionNotify extends Model
{
    protected $fillable = [
        'en_dispat_noti_before_the_driver_completes_the_run',
        'send_e_mail_notification_before_x_not_started_orders',
        'en_driver_noti_when_order_det_are_sent_to_mobile_dev'
    ];
} 