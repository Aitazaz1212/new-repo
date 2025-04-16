<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $table = 'order_status';

    protected $fillable = [
        'order_status',
        'link_status',
        'show_status'
    ];

    protected $casts = [
        'show_status' => 'integer'
    ];

    /**
     * Get the orders with this status.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status_id');
    }

    /**
     * Scope a query to only include visible statuses.
     */
    public function scopeVisible($query)
    {
        return $query->where('show_status', 1);
    }

    /**
     * Scope a query to only include hidden statuses.
     */
    public function scopeHidden($query)
    {
        return $query->where('show_status', 0);
    }

    /**
     * Check if the status is visible.
     */
    public function isVisible(): bool
    {
        return $this->show_status === 1;
    }

    /**
     * Get a list of all visible statuses for a dropdown.
     *
     * @return array<int, string>
     */
    public static function getVisibleForDropdown(): array
    {
        return static::visible()
            ->orderBy('order_status')
            ->pluck('order_status', 'id')
            ->toArray();
    }

    /**
     * Get a list of all statuses for a dropdown.
     *
     * @return array<int, string>
     */
    public static function getAllForDropdown(): array
    {
        return static::orderBy('order_status')
            ->pluck('order_status', 'id')
            ->toArray();
    }
} 