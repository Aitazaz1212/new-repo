<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDetail extends Model
{
    protected $table = 'stockDetail';

    protected $fillable = [
        'sid',
        'weight',
        'cbm',
        'length',
        'width',
        'height',
        'two_man_lift',
        'greater_than_1m',
        'bulky'
    ];

    protected $casts = [
        'sid' => 'integer',
        'weight' => 'float',
        'cbm' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'two_man_lift' => 'boolean',
        'greater_than_1m' => 'boolean',
        'bulky' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the stock item that owns this detail.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'sid');
    }

    /**
     * Calculate the volume in cubic meters.
     */
    public function calculateVolume(): float
    {
        return ($this->length * $this->width * $this->height) / 1000000; // Convert from cm³ to m³
    }

    /**
     * Update the CBM based on dimensions.
     */
    public function updateCbm(): bool
    {
        $this->cbm = $this->calculateVolume();
        return $this->save();
    }

    /**
     * Check if the item requires special handling.
     */
    public function requiresSpecialHandling(): bool
    {
        return $this->two_man_lift || $this->greater_than_1m || $this->bulky;
    }

    /**
     * Get the handling requirements as an array.
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
     * Check if the item exceeds maximum weight limit.
     */
    public function exceedsWeightLimit(float $maxWeight = 25.0): bool
    {
        return $this->weight > $maxWeight;
    }

    /**
     * Get dimensions as formatted string.
     */
    public function getDimensionsString(): string
    {
        return sprintf(
            '%g x %g x %g cm',
            $this->length,
            $this->width,
            $this->height
        );
    }

    /**
     * Scope a query to find items requiring two man lift.
     */
    public function scopeTwoManLift($query)
    {
        return $query->where('two_man_lift', true);
    }

    /**
     * Scope a query to find bulky items.
     */
    public function scopeBulky($query)
    {
        return $query->where('bulky', true);
    }

    /**
     * Scope a query to find items greater than 1m.
     */
    public function scopeGreaterThan1m($query)
    {
        return $query->where('greater_than_1m', true);
    }

    /**
     * Scope a query to find items by weight range.
     */
    public function scopeWeightBetween($query, float $min, float $max)
    {
        return $query->whereBetween('weight', [$min, $max]);
    }

    /**
     * Check if the item fits within given dimensions.
     */
    public function fitsWithinDimensions(float $maxLength, float $maxWidth, float $maxHeight): bool
    {
        return $this->length <= $maxLength &&
               $this->width <= $maxWidth &&
               $this->height <= $maxHeight;
    }

    /**
     * Get the weight in a specific unit.
     */
    public function getWeightIn(string $unit = 'kg'): float
    {
        return match (strtolower($unit)) {
            'g' => $this->weight * 1000,
            'lbs' => $this->weight * 2.20462,
            'oz' => $this->weight * 35.274,
            default => $this->weight, // kg
        };
    }

    /**
     * Set dimensions and update CBM.
     */
    public function setDimensions(float $length, float $width, float $height): bool
    {
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        
        return $this->updateCbm();
    }
} 