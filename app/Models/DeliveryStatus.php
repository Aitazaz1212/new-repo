<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStatus extends Model
{
    protected $table = 'delivery_status';
    
    public $incrementing = false;

    protected $fillable = [
        'delivery_status'
    ];
} 