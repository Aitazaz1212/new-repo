<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $fillable = [
        'get_order_time',
        'backup_time',
        'get_active_order_time',
    ];
}
