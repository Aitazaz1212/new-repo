<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'customerID',
        'orderID',
        'dispID',
        'type',
        'hasSent',
        'cantsend'
    ];

    protected $casts = [
        'customerID' => 'integer',
        'orderID' => 'integer',
        'dispID' => 'integer',
        'hasSent' => 'boolean',
        'cantsend' => 'boolean'
    ];

    // Notification types
    public const TYPE_EMAIL = 'email';
    public const TYPE_SMS = 'sms';
    public const TYPE_PUSH = 'push';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerID');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderID');
    }

    public function disp(): BelongsTo
    {
        return $this->belongsTo(Disp::class, 'dispID');
    }

    /**
     * Scope a query to only include sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('hasSent', true);
    }

    /**
     * Scope a query to only include pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('hasSent', false);
    }

    /**
     * Scope a query to only include sendable notifications.
     */
    public function scopeSendable($query)
    {
        return $query->where('cantsend', false);
    }
} 