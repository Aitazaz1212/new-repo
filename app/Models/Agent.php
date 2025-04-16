<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'paid',
        'owed',
    ];

    protected $casts = [
        'paid' => 'float',
        'owed' => 'float',
    ];
} 