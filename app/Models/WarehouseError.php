<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WarehouseError extends Model
{
    protected $fillable = [
        'note',
        'item',
        'oid',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'oid' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope a query to find errors by order ID.
     */
    public function scopeByOrderId(Builder $query, int $orderId): Builder
    {
        return $query->where('oid', $orderId);
    }

    /**
     * Scope a query to find errors by item.
     */
    public function scopeByItem(Builder $query, string $item): Builder
    {
        return $query->where('item', $item);
    }

    /**
     * Get error summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'item' => $this->item,
            'order_id' => $this->oid,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get all errors for a specific order.
     */
    public static function getErrorsForOrder(int $orderId): Builder
    {
        return static::byOrderId($orderId);
    }

    /**
     * Get all errors for a specific item.
     */
    public static function getErrorsForItem(string $item): Builder
    {
        return static::byItem($item);
    }

    /**
     * Get recent errors.
     *
     * @param int $limit
     */
    public static function getRecentErrors(int $limit = 10): Builder
    {
        return static::orderByDesc('created_at')->limit($limit);
    }
}
