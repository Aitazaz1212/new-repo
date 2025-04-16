<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'oid'
    ];

    protected $casts = [
        'oid' => 'integer'
    ];
} 