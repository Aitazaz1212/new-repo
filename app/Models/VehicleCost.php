<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class VehicleCost extends Model
{
    protected $fillable = [
        'cost',
        'date_incurred',
        'mileage',
        'notes',
        'user_id',
        'vehicle_cost_type_id',
        'vehicle_id'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date_incurred' => 'date',
        'mileage' => 'integer',
        'user_id' => 'integer',
        'vehicle_cost_type_id' => 'integer',
        'vehicle_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the vehicle that owns the cost.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the cost type that owns the cost.
     */
    public function costType(): BelongsTo
    {
        return $this->belongsTo(VehicleCostType::class, 'vehicle_cost_type_id');
    }

    /**
     * Get the user that created the cost.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to find costs by vehicle.
     */
    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope a query to find costs by type.
     */
    public function scopeOfType(Builder $query, int $typeId): Builder
    {
        return $query->where('vehicle_cost_type_id', $typeId);
    }

    /**
     * Scope a query to find costs within a date range.
     */
    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date_incurred', [$start, $end]);
    }

    /**
     * Scope a query to find costs above a certain amount.
     */
    public function scopeAboveCost(Builder $query, float $amount): Builder
    {
        return $query->where('cost', '>', $amount);
    }

    /**
     * Get costs for the current month.
     */
    public static function getCurrentMonthCosts(): Builder
    {
        return static::whereMonth('date_incurred', Carbon::now()->month)
            ->whereYear('date_incurred', Carbon::now()->year);
    }

    /**
     * Calculate cost per mile.
     */
    public function getCostPerMile(): ?float
    {
        if (!$this->mileage) {
            return null;
        }

        return (float) ($this->cost / $this->mileage);
    }

    /**
     * Get formatted cost with currency symbol.
     */
    public function getFormattedCost(string $symbol = '£'): string
    {
        return $symbol . number_format((float) $this->cost, 2);
    }

    /**
     * Get cost summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'cost' => $this->getFormattedCost(),
            'date' => $this->date_incurred->format('Y-m-d'),
            'mileage' => $this->mileage,
            'cost_per_mile' => $this->getCostPerMile(),
            'type' => $this->costType?->type,
            'vehicle' => $this->vehicle?->registration_number,
            'notes' => $this->notes,
            'created_by' => $this->user?->name
        ];
    }

    /**
     * Get total costs for a vehicle in a date range.
     */
    public static function getTotalCostsForVehicle(
        int $vehicleId,
        ?string $start = null,
        ?string $end = null
    ): float {
        $query = static::forVehicle($vehicleId);

        if ($start && $end) {
            $query->betweenDates($start, $end);
        }

        return (float) $query->sum('cost');
    }

    /**
     * Get monthly cost report.
     *
     * @return array<string, array<string, float>>
     */
    public static function getMonthlyReport(int $vehicleId): array
    {
        return static::forVehicle($vehicleId)
            ->selectRaw('YEAR(date_incurred) as year')
            ->selectRaw('MONTH(date_incurred) as month')
            ->selectRaw('SUM(cost) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('year')
            ->map(function ($months) {
                return $months->pluck('total', 'month')->toArray();
            })
            ->toArray();
    }
}