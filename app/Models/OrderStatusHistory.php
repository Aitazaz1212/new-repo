<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';

    protected $fillable = [
        'changed_datetime',
        'from_status',
        'order_id',
        'text',
        'to_status'
    ];

    protected $casts = [
        'changed_datetime' => 'datetime',
        'order_id' => 'integer'
    ];

    /**
     * Get the order that owns the status history.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to get history for a specific order.
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope a query to get history for a specific status change.
     */
    public function scopeForStatusChange($query, string $fromStatus, string $toStatus)
    {
        return $query->where('from_status', $fromStatus)
            ->where('to_status', $toStatus);
    }

    /**
     * Get the duration since the status change.
     */
    public function getDurationSinceChange(): string
    {
        return $this->changed_datetime->diffForHumans();
    }

    /**
     * Check if this was a specific status change.
     */
    public function isStatusChange(string $fromStatus, string $toStatus): bool
    {
        return $this->from_status === $fromStatus && $this->to_status === $toStatus;
    }

    /**
     * Create a new status history entry.
     */
    public static function logStatusChange(
        int $orderId,
        string $fromStatus,
        string $toStatus,
        ?string $text = null
    ): self {
        return static::create([
            'order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'text' => $text,
            'changed_datetime' => now()
        ]);
    }
} 