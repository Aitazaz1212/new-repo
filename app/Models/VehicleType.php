<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class VehicleType extends Model
{
    protected $fillable = [
        'name',
        'ref_no',
        'working_time_before_break',
        'routing_mode',
        'avoid_urban_areas',
        'avoid_london_ultra_low_emission_zone',
        'weight',
        'height',
        'width',
        'length',
        'axle_load'
    ];

    /**
     * Get the vehicles of this type.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'vehicle_type', 'ref_no');
    }

    /**
     * Scope a query to find by reference number.
     */
    public function scopeByRefNo(Builder $query, string $refNo): Builder
    {
        return $query->where('ref_no', $refNo);
    }

    /**
     * Scope a query to find by routing mode.
     */
    public function scopeByRoutingMode(Builder $query, string $mode): Builder
    {
        return $query->where('routing_mode', $mode);
    }

    /**
     * Check if type avoids urban areas.
     */
    public function avoidsUrbanAreas(): bool
    {
        return $this->avoid_urban_areas === 'yes';
    }

    /**
     * Check if type avoids ULEZ.
     */
    public function avoidsUlezZone(): bool
    {
        return $this->avoid_london_ultra_low_emission_zone === 'yes';
    }

    /**
     * Get dimensions as array.
     *
     * @return array<string, string>
     */
    public function getDimensions(): array
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'axle_load' => $this->axle_load
        ];
    }

    /**
     * Get working time before break in minutes.
     */
    public function getWorkingTimeInMinutes(): int
    {
        return (int) $this->working_time_before_break;
    }

    /**
     * Check if vehicle type can access specific area.
     */
    public function canAccessArea(string $areaType): bool
    {
        return match ($areaType) {
            'urban' => !$this->avoidsUrbanAreas(),
            'ulez' => !$this->avoidsUlezZone(),
            default => true
        };
    }

    /**
     * Get type summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ref_no' => $this->ref_no,
            'dimensions' => $this->getDimensions(),
            'routing' => [
                'mode' => $this->routing_mode,
                'avoids_urban' => $this->avoidsUrbanAreas(),
                'avoids_ulez' => $this->avoidsUlezZone()
            ],
            'working_time' => $this->getWorkingTimeInMinutes(),
            'vehicle_count' => $this->vehicles()->count()
        ];
    }

    /**
     * Check if dimensions are within limits.
     */
    public function isWithinDimensions(
        ?float $length = null,
        ?float $width = null,
        ?float $height = null,
        ?float $weight = null
    ): bool {
        if ($length && (float)$this->length < $length) return false;
        if ($width && (float)$this->width < $width) return false;
        if ($height && (float)$this->height < $height) return false;
        if ($weight && (float)$this->weight < $weight) return false;

        return true;
    }

    /**
     * Find suitable vehicle types for given dimensions.
     */
    public static function findSuitableTypes(
        ?float $length = null,
        ?float $width = null,
        ?float $height = null,
        ?float $weight = null
    ): Builder {
        $query = static::query();

        if ($length) $query->where('length', '>=', $length);
        if ($width) $query->where('width', '>=', $width);
        if ($height) $query->where('height', '>=', $height);
        if ($weight) $query->where('weight', '>=', $weight);

        return $query;
    }
}
