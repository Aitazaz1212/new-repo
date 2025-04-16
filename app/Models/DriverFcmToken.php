<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverFcmToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'driver_id',
        'device_id',
        'fcm_token',
    ];

    /**
     * Get the driver that owns the FCM token.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
} 