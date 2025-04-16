<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SpeedZone extends Model
{
    protected $fillable = [
        'name',
        'coordinates',
        'radius',
        'speed_correction_error',
        'color'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'radius' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'speed_correction_error' => 'float'
    ];

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
     * Set the coordinates from an array of lat/lng pairs.
     *
     * @param array<array{lat: float, lng: float}> $coordinates
     */
    public function setCoordinatesAttribute(array $coordinates): void
    {
        $this->attributes['coordinates'] = json_encode($coordinates);
    }

    /**
     * Get the radius as a float value.
     */
    public function getRadiusValue(): float
    {
        return (float) $this->radius;
    }

    /**
     * Get the speed correction error as a float value.
     */
    public function getSpeedCorrectionError(): float
    {
        return (float) $this->speed_correction_error;
    }

    /**
     * Check if a point is within the speed zone.
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
     * Scope a query to find zones by name.
     */
    public function scopeNamed($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Get all points that fall within this speed zone.
     *
     * @param Collection<array{lat: float, lng: float}> $points
     * @return Collection<array{lat: float, lng: float}>
     */
    public function getPointsWithinZone(Collection $points): Collection
    {
        return $points->filter(function ($point) {
            return $this->containsPoint(
                (float) ($point['lat'] ?? $point[0]),
                (float) ($point['lng'] ?? $point[1])
            );
        });
    }

    /**
     * Get the adjusted speed for this zone.
     */
    public function getAdjustedSpeed(float $originalSpeed): float
    {
        $correction = $this->getSpeedCorrectionError();
        return $originalSpeed * (1 + ($correction / 100));
    }

    /**
     * Convert the speed zone to a GeoJSON feature.
     *
     * @return array<string, mixed>
     */
    public function toGeoJson(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'name' => $this->name,
                'color' => $this->getHexColor(),
                'radius' => $this->getRadiusValue(),
                'speed_correction' => $this->getSpeedCorrectionError()
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$this->getCoordinatesCollection()->map(function ($coord) {
                    return [$coord['lng'], $coord['lat']];
                })->toArray()]
            ]
        ];
    }
}
