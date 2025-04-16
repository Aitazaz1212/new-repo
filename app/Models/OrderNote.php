<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    protected $table = 'orderNotes';

    public $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'oid',
        'date',
        'time',
        'notes',
        'staff',
        'driver',
        'pic',
        'id_staff',
        'id_driver'
    ];

    protected $casts = [
        'oid' => 'integer',
        'driver' => 'integer',
        'pic' => 'boolean',
        'id_staff' => 'integer',
        'id_driver' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }

    public function driverRelation(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'id_driver');
    }

    /**
     * Scope a query to only include notes with pictures.
     */
    public function scopeWithPictures($query)
    {
        return $query->where('pic', true);
    }

    /**
     * Scope a query to only include driver notes.
     */
    public function scopeDriverNotes($query)
    {
        return $query->where('driver', 1);
    }

    /**
     * Scope a query to only include staff notes.
     */
    public function scopeStaffNotes($query)
    {
        return $query->where('driver', 0);
    }

    /**
     * Check if the note has a picture.
     */
    public function hasPicture(): bool
    {
        return (bool) $this->pic;
    }

    /**
     * Check if the note is from a driver.
     */
    public function isDriverNote(): bool
    {
        return $this->driver === 1;
    }
} 