<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class VehicleSetting extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'supported_vehicle_requirements',
        'vehicle_type',
        'max_speed',
        'driving_time_correction_factor',
        'cost_per_mile',
        'vehicle_activation_cost',
        'cost_per_order',
        'capacityWeight',
        'driving_time',
        'Run_duration_limit',
        'duty_time_limit',
        'automatic_break_shift',
        'cost_per_hour',
        'run_distance_limit',
        'forDate',
        'time'
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'vehicle_id' => 'integer',
        'supported_vehicle_requirements' => 'array',
        'max_speed' => 'integer',
        'driving_time_correction_factor' => 'integer',
        'cost_per_mile' => 'integer',
        'vehicle_activation_cost' => 'integer',
        'cost_per_order' => 'integer',
        'capacityWeight' => 'integer',
        'driving_time' => 'datetime',
        'Run_duration_limit' => 'datetime',
        'duty_time_limit' => 'datetime',
        'automatic_break_shift' => 'boolean',
        'cost_per_hour' => 'integer',
        'run_distance_limit' => 'integer',
        'forDate' => 'date',
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the vehicle that owns the settings.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver that owns the settings.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Scope a query to find settings by vehicle.
     */
    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope a query to find settings by driver.
     */
    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope a query to find settings by date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('forDate', $date);
    }

    /**
     * Scope a query to find settings by vehicle type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('vehicle_type', $type);
    }

    /**
     * Get the total operational cost per hour.
     */
    public function getTotalCostPerHour(): float
    {
        return $this->cost_per_hour + ($this->vehicle_activation_cost / 60);
    }

    /**
     * Check if vehicle has specific requirement support.
     */
    public function supportsRequirement(string $requirement): bool
    {
        return in_array($requirement, $this->supported_vehicle_requirements ?? [], true);
    }

    /**
     * Add a supported requirement.
     */
    public function addSupportedRequirement(string $requirement): void
    {
        $requirements = $this->supported_vehicle_requirements ?? [];
        $requirements[] = $requirement;
        $this->supported_vehicle_requirements = array_unique($requirements);
        $this->save();
    }

    /**
     * Remove a supported requirement.
     */
    public function removeSupportedRequirement(string $requirement): void
    {
        if ($this->supported_vehicle_requirements) {
            $this->supported_vehicle_requirements = array_values(
                array_diff($this->supported_vehicle_requirements, [$requirement])
            );
            $this->save();
        }
    }

    /**
     * Calculate estimated cost for a given distance.
     */
    public function calculateCostForDistance(float $distance): float
    {
        return ($distance * $this->cost_per_mile) + $this->vehicle_activation_cost;
    }

    /**
     * Check if vehicle is within run distance limit.
     */
    public function isWithinDistanceLimit(float $distance): bool
    {
        return !$this->run_distance_limit || $distance <= $this->run_distance_limit;
    }

    /**
     * Get settings summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'vehicle_type' => $this->vehicle_type,
            'max_speed' => $this->max_speed,
            'capacity_weight' => $this->capacityWeight,
            'costs' => [
                'per_mile' => $this->cost_per_mile,
                'per_hour' => $this->cost_per_hour,
                'per_order' => $this->cost_per_order,
                'activation' => $this->vehicle_activation_cost
            ],
            'time_limits' => [
                'driving' => $this->driving_time,
                'run_duration' => $this->Run_duration_limit,
                'duty' => $this->duty_time_limit
            ],
            'requirements' => $this->supported_vehicle_requirements
        ];
    }
}