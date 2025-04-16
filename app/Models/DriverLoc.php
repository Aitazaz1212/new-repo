<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLoc extends Model
{
    protected $table = 'driverLocs';

    protected $fillable = [
        'driver_id',
        'lat',
        'lng'
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
} 