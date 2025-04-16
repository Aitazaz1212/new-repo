<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverFine extends Model
{
    protected $table = 'driverFines';

    protected $fillable = [
        'driver',
        'amount',
        'paid',
        'date',
        'staff',
        'notes'
    ];

    protected $casts = [
        'amount' => 'float',
        'paid' => 'integer'
    ];
} 