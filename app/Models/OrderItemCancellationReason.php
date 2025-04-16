<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItemCancellationReason extends Model
{
    protected $fillable = [
        'name'
    ];

    /**
     * Get the order items that were cancelled with this reason.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'cancellation_reason_id');
    }

    /**
     * Scope a query to order by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Get a list of all reasons for a dropdown.
     *
     * @return array<int, string>
     */
    public static function getForDropdown(): array
    {
        return static::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
