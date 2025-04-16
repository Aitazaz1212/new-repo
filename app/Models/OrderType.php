<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderType extends Model
{
    protected $table = 'order_type';

    protected $fillable = [
        'order_type'
    ];

    /**
     * Get the orders of this type.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'type_id');
    }

    /**
     * Scope a query to find by type name.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    /**
     * Get a list of all types for a dropdown.
     *
     * @return array<int, string>
     */
    public static function getForDropdown(): array
    {
        return static::orderBy('order_type')
            ->pluck('order_type', 'id')
            ->toArray();
    }

    /**
     * Find or create an order type.
     */
    public static function findOrCreateType(string $type): self
    {
        return static::firstOrCreate(
            ['order_type' => $type]
        );
    }
} 