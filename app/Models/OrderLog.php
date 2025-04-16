<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLog extends Model
{
    protected $table = 'orders_log';

    protected $fillable = [
        'ip',
        'proxy',
        'oid',
        'local',
        'user_id',
        'actions',
        'status',
        'eta',
        'timeAndDate'
    ];

    protected $casts = [
        'oid' => 'integer',
        'user_id' => 'integer',
        'timeAndDate' => 'datetime'
    ];

    /**
     * Get the order associated with this log entry.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }

    /**
     * Get the user associated with this log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver associated with this log entry.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class)->select(['id' , 'name' , 'uName']);
    }

    /**
     * Scope a query to only include logs for a specific order.
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('oid', $orderId);
    }

    /**
     * Scope a query to only include logs from a specific IP.
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip', $ip);
    }

    /**
     * Scope a query to only include logs from a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs with a specific action.
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('actions', $action);
    }

    /**
     * Create a new log entry.
     */
    public static function logAction(
        int $orderId,
        string $action,
        ?string $status = null,
        ?string $eta = null,
        ?int $userId = null
    ): self {
        return static::create([
            'oid' => $orderId,
            'actions' => $action,
            'status' => $status,
            'eta' => $eta,
            'user_id' => $userId,
            'ip' => request()->ip(),
            'proxy' => request()->server('HTTP_X_FORWARDED_FOR'),
            'local' => request()->server('SERVER_ADDR'),
            'timeAndDate' => now()
        ]);
    }

    /**
     * Get the log entry's action and status as a formatted string.
     */
    public function getActionSummary(): string
    {
        $summary = $this->actions;
        if ($this->status) {
            $summary .= " (Status: {$this->status})";
        }
        if ($this->eta) {
            $summary .= " (ETA: {$this->eta})";
        }
        return $summary;
    }
}
