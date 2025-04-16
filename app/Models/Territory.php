<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Territory extends Model
{
    protected $fillable = [
        'name',
        'ref_no',
        'coordinates',
        'radius',
        'warehouse_id',
        'color',
        'group_id'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'radius' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the vehicles associated with the territory.
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'territory_vehicle', 'territorie_id', 'vehicle_id')
            ->withTimestamps();
    }

    /**
     * Get the group this territory belongs to.
     */
    public function group()
    {
        return $this->belongsTo(TerritoriesGroup::class, 'group_id');
    }

    /**
     * Get the warehouse this territory belongs to.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the coordinates as a collection of lat/lng pairs.
     *
     * @return Collection<array{lat: float, lng: float}>
     */
    public function getCoordinatesCollection(): Collection
    {
        $coordinates = json_decode($this->coordinates, true);
        return collect($coordinates)->map(function ($coordinate) {
            return [
                'lat' => (float) ($coordinate['lat'] ?? $coordinate[0]),
                'lng' => (float) ($coordinate['lng'] ?? $coordinate[1])
            ];
        });
    }

    /**
     * Get the radius as a float value.
     */
    public function getRadiusValue(): float
    {
        return (float) $this->radius;
    }

    /**
     * Check if a point is within the territory.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $point = ['lat' => $lat, 'lng' => $lng];
        $radius = $this->getRadiusValue();

        return $this->getCoordinatesCollection()->contains(function ($coordinate) use ($point, $radius) {
            return $this->calculateDistance(
                $coordinate['lat'],
                $coordinate['lng'],
                $point['lat'],
                $point['lng']
            ) <= $radius;
        });
    }

    /**
     * Calculate the distance between two points using the Haversine formula.
     */
    protected function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($lat1) * cos($lat2) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get the color in hexadecimal format.
     */
    public function getHexColor(): string
    {
        return $this->color ?? '#000000';
    }

    /**
     * Scope a query to find territories by warehouse.
     */
    public function scopeForWarehouse(Builder $query, string $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to find territories by group.
     */
    public function scopeInGroup(Builder $query, string $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Convert the territory to a GeoJSON feature.
     *
     * @return array<string, mixed>
     */
    public function toGeoJson(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'name' => $this->name,
                'ref_no' => $this->ref_no,
                'color' => $this->getHexColor(),
                'radius' => $this->getRadiusValue()
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$this->getCoordinatesCollection()->map(function ($coord) {
                    return [$coord['lng'], $coord['lat']];
                })->toArray()]
            ]
        ];
    }

    /**
     * Get all assigned vehicles' registration numbers.
     *
     * @return array<string>
     */
    public function getAssignedVehicleRegistrations(): array
    {
        return $this->vehicles()
            ->pluck('registration_number')
            ->toArray();
    }

    /**
     * Find territories that overlap with given coordinates.
     */
    public static function findOverlapping(float $lat, float $lng): Collection
    {
        return static::all()->filter(function ($territory) use ($lat, $lng) {
            return $territory->containsPoint($lat, $lng);
        });
    }
}
