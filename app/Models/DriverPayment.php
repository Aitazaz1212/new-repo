<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverPayment extends Model
{
    protected $table = 'driverPayments';

    protected $fillable = [
        'dates',
        'amount',
        'date',
        'driver',
        'staff'
    ];

    protected $casts = [
        'amount' => 'float'
    ];
} 