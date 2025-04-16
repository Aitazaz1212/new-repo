<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'imei',
        'tel',
        'driver',
        'token'
    ];

    protected $casts = [
        'driver' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver');
    }
} 