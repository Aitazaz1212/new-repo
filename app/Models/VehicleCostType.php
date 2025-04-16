<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VehicleCostType extends Model
{
    protected $fillable = [
        'id',
        'description',
        'type'
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the vehicle costs associated with this type.
     */
    public function vehicleCosts(): HasMany
    {
        return $this->hasMany(VehicleCost::class, 'cost_type_id', 'id');
    }

    /**
     * Scope a query to find cost types by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get total costs for this type.
     */
    public function getTotalCosts(): float
    {
        return $this->vehicleCosts()->sum('amount');
    }

    /**
     * Get costs for a specific vehicle.
     */
    public function getCostsForVehicle(int $vehicleId): Collection
    {
        return $this->vehicleCosts()
            ->where('vehicle_id', $vehicleId)
            ->get();
    }

    /**
     * Get total costs for a specific vehicle.
     */
    public function getTotalCostsForVehicle(int $vehicleId): float
    {
        return $this->vehicleCosts()
            ->where('vehicle_id', $vehicleId)
            ->sum('amount');
    }

    /**
     * Get costs within a date range.
     */
    public function getCostsInDateRange(string $startDate, string $endDate): Collection
    {
        return $this->vehicleCosts()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get summary of costs by vehicle.
     *
     * @return array<int, float>
     */
    public function getCostsByVehicle(): array
    {
        return $this->vehicleCosts()
            ->select('vehicle_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('vehicle_id')
            ->pluck('total', 'vehicle_id')
            ->toArray();
    }

    /**
     * Check if type has any associated costs.
     */
    public function hasCosts(): bool
    {
        return $this->vehicleCosts()->exists();
    }

    /**
     * Get the most expensive vehicles for this cost type.
     */
    public function getMostExpensiveVehicles(int $limit = 5): Collection
    {
        return $this->vehicleCosts()
            ->select('vehicle_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('vehicle_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Get monthly cost summary for this type.
     *
     * @return array<string, float>
     */
    public function getMonthlyCostSummary(): array
    {
        return $this->vehicleCosts()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    /**
     * Get cost type summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'total_costs' => $this->getTotalCosts(),
            'cost_count' => $this->vehicleCosts()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get all unique types.
     *
     * @return array<string>
     */
    public static function getAllTypes(): array
    {
        return static::distinct('type')
            ->pluck('type')
            ->toArray();
    }
}
