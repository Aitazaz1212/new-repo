<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TerritoryDriver extends Model
{
    protected $table = 'territory_driver';

    protected $fillable = [
        'territory_id',
        'driver_id'
    ];

    protected $casts = [
        'territory_id' => 'integer',
        'driver_id' => 'integer'
    ];

    /**
     * Get the territory that owns the assignment.
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_id');
    }

    /**
     * Get the driver that owns the assignment.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Scope a query to find assignments by territory.
     */
    public function scopeForTerritory(Builder $query, int $territoryId): Builder
    {
        return $query->where('territory_id', $territoryId);
    }

    /**
     * Scope a query to find assignments by driver.
     */
    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Check if a driver is assigned to a territory.
     */
    public static function isDriverAssigned(int $driverId, int $territoryId): bool
    {
        return static::where('driver_id', $driverId)
            ->where('territory_id', $territoryId)
            ->exists();
    }

    /**
     * Get all territories for a driver.
     */
    public static function getTerritoriesForDriver(int $driverId): Collection
    {
        return static::where('driver_id', $driverId)
            ->with('territory')
            ->get()
            ->pluck('territory');
    }

    /**
     * Get all drivers for a territory.
     */
    public static function getDriversForTerritory(int $territoryId): Collection
    {
        return static::where('territory_id', $territoryId)
            ->with('driver')
            ->get()
            ->pluck('driver');
    }

    /**
     * Assign multiple drivers to a territory.
     *
     * @param array<int> $driverIds
     */
    public static function assignDriversToTerritory(int $territoryId, array $driverIds): void
    {
        $data = array_map(function($driverId) use ($territoryId) {
            return [
                'territory_id' => $territoryId,
                'driver_id' => $driverId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $driverIds);

        static::insert($data);
    }

    /**
     * Remove all driver assignments from a territory.
     */
    public static function clearTerritoryAssignments(int $territoryId): int
    {
        return static::where('territory_id', $territoryId)->delete();
    }

    /**
     * Remove all territory assignments from a driver.
     */
    public static function clearDriverAssignments(int $driverId): int
    {
        return static::where('driver_id', $driverId)->delete();
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
            'driver' => $this->driver?->name,
            'territory' => $this->territory?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}