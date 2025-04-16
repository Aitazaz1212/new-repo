<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteDay extends Model
{
    protected $table = 'routeDays';

    protected $primaryKey = 'routeDaysID';

    public $incrementing = false;

    protected $fillable = [
        'routeDaysID',
        'route',
        'day',
        'max',
        'route_id'
    ];

    protected $casts = [
        'routeDaysID' => 'integer',
        'max' => 'integer',
        'route_id' => 'integer'
    ];

    /**
     * Get the route that owns this route day.
     */
    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }

    /**
     * Scope a query to find route days by route.
     */
    public function scopeForRoute($query, string $route)
    {
        return $query->where('route', $route);
    }

    /**
     * Scope a query to find route days by day.
     */
    public function scopeForDay($query, string $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Check if the route has reached its maximum capacity for the day.
     */
    public function hasReachedMaxCapacity(int $currentCount): bool
    {
        return $currentCount >= $this->max;
    }

    /**
     * Get remaining capacity for the route day.
     */
    public function getRemainingCapacity(int $currentCount): int
    {
        return max(0, $this->max - $currentCount);
    }

    /**
     * Update the maximum capacity for the route day.
     */
    public function updateMaxCapacity(int $newMax): bool
    {
        if ($newMax < 0) {
            return false;
        }

        $this->max = $newMax;
        return $this->save();
    }

    /**
     * Get all route days for a specific day of the week.
     */
    public static function getRoutesForDay(string $day): array
    {
        return static::where('day', $day)
            ->pluck('route', 'routeDaysID')
            ->toArray();
    }

    /**
     * Get all days for a specific route.
     */
    public static function getDaysForRoute(string $route): array
    {
        return static::where('route', $route)
            ->pluck('day', 'routeDaysID')
            ->toArray();
    }

    /**
     * Check if a route operates on a specific day.
     */
    public static function isRouteOperatingOnDay(string $route, string $day): bool
    {
        return static::where('route', $route)
            ->where('day', $day)
            ->exists();
    }
}