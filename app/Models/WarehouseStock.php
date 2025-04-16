<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WarehouseStock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'stock_parts_id',
        'quantity'
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'stock_parts_id' => 'integer',
        'quantity' => 'integer'
    ];

    /**
     * Get the warehouse that owns the stock.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the stock part that owns the stock.
     */
    public function stockPart(): BelongsTo
    {
        return $this->belongsTo(StockPart::class, 'stock_parts_id');
    }

    /**
     * Scope a query to find stock by warehouse.
     */
    public function scopeInWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to find stock by part.
     */
    public function scopeForPart(Builder $query, int $partId): Builder
    {
        return $query->where('stock_parts_id', $partId);
    }

    /**
     * Scope a query to find stock with quantity greater than zero.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to find out of stock items.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Update stock quantity.
     */
    public function updateQuantity(int $newQuantity): bool
    {
        $this->quantity = $newQuantity;
        return $this->save();
    }

    /**
     * Adjust stock quantity by amount.
     */
    public function adjustQuantity(int $adjustment): bool
    {
        return $this->updateQuantity($this->quantity + $adjustment);
    }

    /**
     * Check if item is in stock.
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if quantity is sufficient.
     */
    public function hasQuantity(int $required): bool
    {
        return $this->quantity >= $required;
    }

    /**
     * Get stock levels across all warehouses for a part.
     */
    public static function getStockLevelsForPart(int $partId): Collection
    {
        return static::forPart($partId)
            ->with('warehouse')
            ->get()
            ->map(function ($stock) {
                return [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse?->name,
                    'quantity' => $stock->quantity
                ];
            });
    }

    /**
     * Get total stock quantity for a part across all warehouses.
     */
    public static function getTotalStockForPart(int $partId): int
    {
        return static::forPart($partId)->sum('quantity');
    }

    /**
     * Get stock summary for a warehouse.
     */
    public static function getWarehouseStockSummary(int $warehouseId): Collection
    {
        return static::inWarehouse($warehouseId)
            ->with('stockPart')
            ->get()
            ->map(function ($stock) {
                return [
                    'part_id' => $stock->stock_parts_id,
                    'part_name' => $stock->stockPart?->name,
                    'quantity' => $stock->quantity,
                    'in_stock' => $stock->isInStock()
                ];
            });
    }

    /**
     * Transfer stock between warehouses.
     */
    public static function transferStock(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $partId,
        int $quantity
    ): bool {
        $fromStock = static::inWarehouse($fromWarehouseId)
            ->forPart($partId)
            ->first();

        if (!$fromStock || !$fromStock->hasQuantity($quantity)) {
            return false;
        }

        $toStock = static::firstOrCreate([
            'warehouse_id' => $toWarehouseId,
            'stock_parts_id' => $partId
        ], ['quantity' => 0]);

        $fromStock->adjustQuantity(-$quantity);
        $toStock->adjustQuantity($quantity);

        return true;
    }
}
