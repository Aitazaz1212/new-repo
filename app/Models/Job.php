<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at'
    ];

    protected $casts = [
        'attempts' => 'integer',
        'reserved_at' => 'integer',
        'available_at' => 'integer',
        'created_at' => 'integer'
    ];
}
