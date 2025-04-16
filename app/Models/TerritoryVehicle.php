<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritoryVehicle extends Pivot
{
    protected $table = 'territory_vehicle';

    protected $fillable = [
        'territory_id',
        'vehicle_id'
    ];

    protected $casts = [
        'territory_id' => 'integer',
        'vehicle_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the territory that owns the pivot.
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_id');
    }

    /**
     * Get the vehicle that owns the pivot.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Create a new assignment.
     */
    public function createAssignment(int $territoryId, int $vehicleId): void
    {
        $this->create([
            'territory_id' => $territoryId,
            'vehicle_id' => $vehicleId
        ]);
    }

    /**
     * Scope a query to find assignments by territory.
     */
    public function scopeForTerritory($query, int $territoryId)
    {
        return $query->where('territory_id', $territoryId);
    }

    /**
     * Scope a query to find assignments by vehicle.
     */
    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Check if a vehicle is assigned to a territory.
     */
    public static function isVehicleAssigned(int $vehicleId, int $territoryId): bool
    {
        return static::where('vehicle_id', $vehicleId)
            ->where('territory_id', $territoryId)
            ->exists();
    }

    /**
     * Get all vehicles for a territory.
     */
    public static function getVehiclesForTerritory(int $territoryId): array
    {
        return static::where('territory_id', $territoryId)
            ->pluck('vehicle_id')
            ->toArray();
    }

    /**
     * Get all territories for a vehicle.
     */
    public static function getTerritoriesForVehicle(int $vehicleId): array
    {
        return static::where('vehicle_id', $vehicleId)
            ->pluck('territory_id')
            ->toArray();
    }

    /**
     * Assign multiple vehicles to a territory.
     *
     * @param array<int> $vehicleIds
     */
    public static function assignVehiclesToTerritory(int $territoryId, array $vehicleIds): void
    {
        $data = array_map(function($vehicleId) use ($territoryId) {
            return [
                'territory_id' => $territoryId,
                'vehicle_id' => $vehicleId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $vehicleIds);

        static::insert($data);
    }

    /**
     * Remove all vehicle assignments from a territory.
     */
    public static function clearTerritoryAssignments(int $territoryId): int
    {
        return static::where('territory_id', $territoryId)->delete();
    }

    /**
     * Remove all territory assignments from a vehicle.
     */
    public static function clearVehicleAssignments(int $vehicleId): int
    {
        return static::where('vehicle_id', $vehicleId)->delete();
    }
} 