<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDay extends Model
{
    protected $table = 'driverDays';

    protected $fillable = [
        'name',
        'day',
        'driver_id',
        'start_time',
        'end_time',
        'start_driving_exactly_from_the_shift_start',
        'checked',
        'start_day',
        'end_day',
        'date'
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'start_driving_exactly_from_the_shift_start' => 'boolean',
        'checked' => 'boolean',
        'date' => 'date'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
} 