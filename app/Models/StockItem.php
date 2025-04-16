<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class StockItem extends Model
{
    protected $fillable = [
        'cost',
        'itemCode',
        'itemName',
        'itemDescription',
        'id_company',
        'sms',
        'itemQty',
        'itemAlloc',
        'itemOnOrder',
        'needs_attention',
        'retail',
        'wholesale',
        'weight',
        'dimensions',
        'bin',
        'actual',
        'pieces',
        'isMulti',
        'warehouse',
        'label',
        'blocked',
        'seller',
        'cat',
        'qty_in_stock',
        'qty_on_so',
        'qty_ordered',
        'category_id',
        'beds_price',
        'ebay_price',
        'route_id',
        'colour',
        'gross_weight',
        'manifest_description',
        'model_no',
        'net_weight',
        'cbm',
        'two_man_lift',
        'greater_than_1m',
        'parts',
        'bulky',
        'thickness',
        'cube',
        'height',
        'length',
        'width',
        'product_size',
        'stock_category_id',
        'withdrawn',
        'always_in_stock',
        'isActive'
    ];

    protected $casts = [
        'cost' => 'float',
        'id_company' => 'integer',
        'sms' => 'integer',
        'itemQty' => 'integer',
        'itemAlloc' => 'integer',
        'itemOnOrder' => 'integer',
        'needs_attention' => 'boolean',
        'retail' => 'float',
        'wholesale' => 'float',
        'weight' => 'decimal:3',
        'actual' => 'integer',
        'pieces' => 'integer',
        'warehouse' => 'integer',
        'label' => 'integer',
        'blocked' => 'integer',
        'seller' => 'integer',
        'qty_in_stock' => 'integer',
        'qty_on_so' => 'integer',
        'qty_ordered' => 'integer',
        'category_id' => 'integer',
        'beds_price' => 'float',
        'ebay_price' => 'float',
        'route_id' => 'integer',
        'gross_weight' => 'decimal:2',
        'net_weight' => 'decimal:2',
        'cbm' => 'float',
        'two_man_lift' => 'boolean',
        'greater_than_1m' => 'boolean',
        'parts' => 'float',
        'bulky' => 'float',
        'thickness' => 'float',
        'cube' => 'float',
        'height' => 'float',
        'length' => 'float',
        'width' => 'float',
        'stock_category_id' => 'integer',
        'withdrawn' => 'boolean',
        'always_in_stock' => 'boolean',
        'isActive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the category that owns the stock item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(StockCategory::class, 'stock_category_id');
    }

    /**
     * Get the route that owns the stock item.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }

    /**
     * Calculate available quantity.
     */
    public function getAvailableQuantity(): int
    {
        return $this->qty_in_stock - $this->qty_on_so;
    }

    /**
     * Check if item needs restocking.
     */
    public function needsRestocking(): bool
    {
        return $this->getAvailableQuantity() <= 0 && $this->always_in_stock;
    }

    /**
     * Calculate total value of stock.
     */
    public function getTotalStockValue(): float
    {
        return $this->qty_in_stock * $this->cost;
    }

    /**
     * Calculate potential retail value.
     */
    public function getPotentialRetailValue(): float
    {
        return $this->qty_in_stock * $this->retail;
    }

    /**
     * Get dimensions as array.
     *
     * @return array<string, float>
     */
    public function getDimensionsArray(): array
    {
        return [
            'length' => $this->length ?? 0,
            'width' => $this->width ?? 0,
            'height' => $this->height ?? 0
        ];
    }

    /**
     * Calculate volume in cubic meters.
     */
    public function calculateVolume(): float
    {
        return ($this->length * $this->width * $this->height) / 1000000; // Convert from cm³ to m³
    }

    /**
     * Update stock levels.
     */
    public function updateStock(int $quantity, string $type = 'in'): bool
    {
        if ($type === 'in') {
            $this->qty_in_stock += $quantity;
        } else {
            $this->qty_in_stock = max(0, $this->qty_in_stock - $quantity);
        }

        return $this->save();
    }

    /**
     * Scope a query to only include active items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope a query to only include items needing attention.
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where('needs_attention', true);
    }

    /**
     * Scope a query to only include items requiring two man lift.
     */
    public function scopeTwoManLift(Builder $query): Builder
    {
        return $query->where('two_man_lift', true);
    }

    /**
     * Scope a query to find items by category.
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('stock_category_id', $categoryId);
    }

    /**
     * Scope a query to find items needing restock.
     */
    public function scopeNeedsRestock(Builder $query): Builder
    {
        return $query->where('always_in_stock', true)
            ->whereRaw('qty_in_stock <= qty_on_so');
    }

    /**
     * Get handling requirements.
     *
     * @return array<string>
     */
    public function getHandlingRequirements(): array
    {
        $requirements = [];

        if ($this->two_man_lift) {
            $requirements[] = 'Two Man Lift Required';
        }

        if ($this->greater_than_1m) {
            $requirements[] = 'Length Greater Than 1m';
        }

        if ($this->bulky) {
            $requirements[] = 'Bulky Item';
        }

        return $requirements;
    }

    /**
     * Check if the item requires special handling.
     */
    public function requiresSpecialHandling(): bool
    {
        return $this->two_man_lift || $this->greater_than_1m || (bool)$this->bulky;
    }

    /**
     * Get the profit margin percentage.
     */
    public function getProfitMargin(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }

        return (($this->retail - $this->cost) / $this->cost) * 100;
    }

    /**
     * Get stockitem details
     */
    public function stockDetail(): HasOne
    {
        return $this->hasOne(StockDetail::class,'sid','id');
    }
}
