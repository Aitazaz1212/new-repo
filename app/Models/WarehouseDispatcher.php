<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WarehouseDispatcher extends Model
{
    protected $table = 'warehouse_dispatchers';

    protected $fillable = [
        'dispatcher_id',
        'warehouse_id'
    ];

    protected $casts = [
        'dispatcher_id' => 'integer',
        'warehouse_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the warehouse that owns the dispatcher.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the dispatcher that owns the warehouse assignment.
     */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    /**
     * Scope a query to find assignments by warehouse.
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to find assignments by dispatcher.
     */
    public function scopeForDispatcher(Builder $query, int $dispatcherId): Builder
    {
        return $query->where('dispatcher_id', $dispatcherId);
    }

    /**
     * Check if a dispatcher is assigned to a warehouse.
     */
    public static function isDispatcherAssigned(int $dispatcherId, int $warehouseId): bool
    {
        return static::where('dispatcher_id', $dispatcherId)
            ->where('warehouse_id', $warehouseId)
            ->exists();
    }

    /**
     * Get all warehouses for a dispatcher.
     */
    public static function getWarehousesForDispatcher(int $dispatcherId): Collection
    {
        return static::where('dispatcher_id', $dispatcherId)
            ->with('warehouse')
            ->get()
            ->pluck('warehouse');
    }

    /**
     * Get all dispatchers for a warehouse.
     */
    public static function getDispatchersForWarehouse(int $warehouseId): Collection
    {
        return static::where('warehouse_id', $warehouseId)
            ->with('dispatcher')
            ->get()
            ->pluck('dispatcher');
    }

    /**
     * Assign multiple dispatchers to a warehouse.
     *
     * @param array<int> $dispatcherIds
     */
    public static function assignDispatchersToWarehouse(int $warehouseId, array $dispatcherIds): void
    {
        $data = array_map(function ($dispatcherId) use ($warehouseId) {
            return [
                'warehouse_id' => $warehouseId,
                'dispatcher_id' => $dispatcherId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $dispatcherIds);

        static::insert($data);
    }

    /**
     * Remove all dispatcher assignments from a warehouse.
     */
    public static function clearWarehouseAssignments(int $warehouseId): int
    {
        return static::where('warehouse_id', $warehouseId)->delete();
    }

    /**
     * Remove all warehouse assignments from a dispatcher.
     */
    public static function clearDispatcherAssignments(int $dispatcherId): int
    {
        return static::where('dispatcher_id', $dispatcherId)->delete();
    }

    /**
     * Get assignment summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'dispatcher' => $this->dispatcher?->name,
            'warehouse' => $this->warehouse?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
