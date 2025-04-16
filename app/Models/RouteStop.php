<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    protected $table = 'routeStops';

    protected $fillable = [
        'routeID',
        'postCode'
    ];

    protected $casts = [
        'routeID' => 'integer'
    ];

    /**
     * Get the route that owns this stop.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'routeID');
    }

    /**
     * Scope a query to find stops by route ID.
     */
    public function scopeForRoute($query, int $routeId)
    {
        return $query->where('routeID', $routeId);
    }

    /**
     * Scope a query to find stops by postcode.
     */
    public function scopeForPostcode($query, string $postcode)
    {
        return $query->where('postCode', $postcode);
    }

    /**
     * Format the postcode to a standard format.
     */
    public function setPostCodeAttribute(string $value): void
    {
        $this->attributes['postCode'] = strtoupper(str_replace(' ', '', $value));
    }

    /**
     * Get the formatted postcode.
     */
    public function getFormattedPostcodeAttribute(): string
    {
        $postcode = $this->attributes['postCode'];
        $length = strlen($postcode);
        
        if ($length > 3) {
            return substr($postcode, 0, -3) . ' ' . substr($postcode, -3);
        }
        
        return $postcode;
    }

    /**
     * Check if a postcode exists in any route.
     */
    public static function hasPostcode(string $postcode): bool
    {
        return static::where('postCode', strtoupper(str_replace(' ', '', $postcode)))->exists();
    }

    /**
     * Find route ID for a given postcode.
     */
    public static function findRouteForPostcode(string $postcode): ?int
    {
        $stop = static::where('postCode', strtoupper(str_replace(' ', '', $postcode)))->first();
        return $stop ? $stop->routeID : null;
    }

    /**
     * Get all postcodes for a route.
     *
     * @return array<string>
     */
    public static function getPostcodesForRoute(int $routeId): array
    {
        return static::where('routeID', $routeId)
            ->pluck('postCode')
            ->toArray();
    }

    /**
     * Add multiple postcodes to a route.
     *
     * @param array<string> $postcodes
     */
    public static function addPostcodesToRoute(int $routeId, array $postcodes): void
    {
        $data = array_map(function($postcode) use ($routeId) {
            return [
                'routeID' => $routeId,
                'postCode' => strtoupper(str_replace(' ', '', $postcode))
            ];
        }, $postcodes);

        static::insert($data);
    }

    /**
     * Remove multiple postcodes from a route.
     *
     * @param array<string> $postcodes
     */
    public static function removePostcodesFromRoute(int $routeId, array $postcodes): void
    {
        static::where('routeID', $routeId)
            ->whereIn('postCode', array_map(function($postcode) {
                return strtoupper(str_replace(' ', '', $postcode));
            }, $postcodes))
            ->delete();
    }
} 