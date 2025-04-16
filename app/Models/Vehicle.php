<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\VehicleTerritory;

class Vehicle extends Model
{
    protected $fillable = [
        'reg',
        'height',
        'width',
        'depth',
        'weight',
        'description',
        'user_id',
        'name',
        'vehicle_type',
        'assigned_device',
        'tcp_source',
        'supported_vehicle',
        'max_speed',
        'driving_time_correction_factor',
        'cost_per_mile',
        'vehicle_activation_cost',
        'cost_per_order',
        'capacity_weight',
        'run_distance_limit',
        'distribution_centre_id',
        'driver_id',
        'external_id',
        'territories',
        'comment',
        'manufacturer_info',
        'vin',
        'stand_down',
        'archived',
        'color',
        'id_route',
        'volume'
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'depth' => 'decimal:2',
        'weight' => 'decimal:2',
        'volume' => 'float',
        'user_id' => 'integer',
        'driver_id' => 'integer',
        'distribution_centre_id' => 'integer',
        'territories' => 'array'
    ];

    /**
     * Get the user that owns the vehicle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver assigned to the vehicle.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the distribution centre that owns the vehicle.
     */
    public function distributionCentre(): BelongsTo
    {
        return $this->belongsTo(DistributionCentre::class);
    }

    /**
     * Get the vehicle costs.
     */
    public function costs(): HasMany
    {
        return $this->hasMany(VehicleCost::class);
    }

    /**
     * Get the vehicle territories.
     */
    public function vehicleTerritory(): HasMany
    {
        return $this->hasMany(TerritoryVehicle::class);
    }

    /**
     * Get the vehicle settings.
     */
    public function vehicleSetting()
    {
        return $this->hasOne(VehicleSetting::class);
    }

    /**
     * Get the vehicle dispatches.
     */
    public function disp()
    {
        return $this->hasMany(Disp::class, 'van');
    }

    /**
     * Get the vehicle dispatches with selected columns.
     * The "dispforpd" relation is primarily created for use on the frontend.
     */
    public function dispforpd(){
        return $this->hasMany(Disp::class,'van','id')
        ->select('van', 'oid', 'num')
        ->orderBy('num', 'asc');

    }

    /**
     * Get the vehicle cost.
     */
    public function vehicleCost()
    {
        return $this->hasOne(VehicleCost::class);
    }

    /**
     * Get the vehicle warehouse.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'distribution_centre_id');
    }

    public function warehouse_name()
    {
        return $this->warehouse()->select('id', 'name', 'latitude', 'longitude', 'ref_number', 'location_of_warehouse', 'name_of_warehouse', 'location');
    }

     /**
     * Get the vehicle warehouse.
     * The "warehousefullobject" relation is primarily created for use on the frontend.
     */
    public function  warehousefullobject() {
        return $this->belongsTo(Warehouse::class, 'distribution_centre_id', 'id');
    }

    /**
     * Get vehicle dimensions.
     *
     * @return array<string, float>
     */
    public function getDimensions(): array
    {
        return [
            'height' => (float) $this->height,
            'width' => (float) $this->width,
            'depth' => (float) $this->depth,
            'weight' => (float) $this->weight,
            'volume' => $this->volume
        ];
    }

    /**
     * Calculate total cost for distance.
     */
    public function calculateCostForDistance(float $distance): float
    {
        $costPerMile = (float) $this->cost_per_mile;
        $activationCost = (float) $this->vehicle_activation_cost;

        return ($distance * $costPerMile) + $activationCost;
    }

    /**
     * Check if vehicle is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived === 'yes';
    }

    /**
     * Check if vehicle is stood down.
     */
    public function isStoodDown(): bool
    {
        return $this->stand_down === 'yes';
    }

    /**
     * Check if vehicle is available.
     */
    public function isAvailable(): bool
    {
        return !$this->isArchived() && !$this->isStoodDown();
    }

    /**
     * Scope a query to only include available vehicles.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('archived', '!=', 'yes')
            ->where('stand_down', '!=', 'yes');
    }

    /**
     * Scope a query to find by registration.
     */
    public function scopeByRegistration(Builder $query, string $reg): Builder
    {
        return $query->where('reg', $reg);
    }

    /**
     * Scope a query to find by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('vehicle_type', $type);
    }

    /**
     * Scope a query to find by driver.
     */
    public function scopeByDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Get vehicle summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reg' => $this->reg,
            'type' => $this->vehicle_type,
            'dimensions' => $this->getDimensions(),
            'driver' => $this->driver?->name,
            'status' => [
                'archived' => $this->isArchived(),
                'stood_down' => $this->isStoodDown(),
                'available' => $this->isAvailable()
            ],
            'costs' => [
                'per_mile' => $this->cost_per_mile,
                'activation' => $this->vehicle_activation_cost,
                'per_order' => $this->cost_per_order
            ],
            'specifications' => [
                'max_speed' => $this->max_speed,
                'capacity_weight' => $this->capacity_weight,
                'run_distance_limit' => $this->run_distance_limit,
                'vin' => $this->vin
            ]
        ];
    }

   /**
     * Vehicles hasmany routes
     */
    public function dispatchedRoutes(): HasMany
    {
        return $this->hasMany(DispRoute::class , 'vehicle' ,'id');
    }
}
