<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Warehouse extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'location',
        'user_id',
        'ref_number',
        'address_prefix',
        'phone',
        'dispatcher',
        'driving_time_correction_factor',
        'daily_driving_limt',
        'duty_time_limt',
        'run_duration_limt',
        'collection_after_deliveries',
        'start_fo_day_location',
        'end_of_day_location',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'holidays',
        'latitude',
        'longitude',
        'postalCode',
        'name_of_warehouse',
        'location_of_warehouse',
        'checked',
        'visitDistributionCenter',
        'vistDistributionLocation'
    ];

    protected $casts = [
        'name' => 'integer',
        'dispatcher' => 'array',
        'sunday' => 'array',
        'monday' => 'array',
        'tuesday' => 'array',
        'wednesday' => 'array',
        'thursday' => 'array',
        'friday' => 'array',
        'saturday' => 'array',
        'holidays' => 'array',
        'checked' => 'integer'
    ];

    /**
     * Get the user that owns the warehouse.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stock items for the warehouse.
     */
    public function stock(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * Get the dispatchers for the warehouse.
     */
    public function dispatchers(): HasMany
    {
        return $this->hasMany(WarehouseDispatcher::class);
    }

    /**
     * Get the working hours for a specific day.
     */
    public function getWorkingHours(string $day): ?array
    {
        return $this->getAttribute(strtolower($day));
    }

    /**
     * Check if warehouse is open on a specific day.
     */
    public function isOpenOn(string $day): bool
    {
        $hours = $this->getWorkingHours($day);
        return !empty($hours) && $hours['isOpen'] ?? false;
    }

    /**
     * Get warehouse coordinates.
     *
     * @return array{lat: float, lng: float}
     */
    public function getCoordinates(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude
        ];
    }

    /**
     * Scope a query to find warehouses by postal code.
     */
    public function scopeByPostalCode(Builder $query, string $postalCode): Builder
    {
        return $query->where('postalCode', $postalCode);
    }

    /**
     * Scope a query to find warehouses by reference number.
     */
    public function scopeByRefNumber(Builder $query, string $refNumber): Builder
    {
        return $query->where('ref_number', $refNumber);
    }

    /**
     * Get warehouse address.
     */
    public function getFullAddress(): string
    {
        return trim(sprintf('%s %s %s',
            $this->address_prefix ?? '',
            $this->location_of_warehouse ?? '',
            $this->postalCode ?? ''
        ));
    }

    /**
     * Check if warehouse handles collections after deliveries.
     */
    public function handlesCollectionsAfterDeliveries(): bool
    {
        return $this->collection_after_deliveries === 'yes';
    }

    /**
     * Get warehouse operating hours summary.
     *
     * @return array<string, array|null>
     */
    public function getOperatingHours(): array
    {
        return [
            'monday' => $this->monday,
            'tuesday' => $this->tuesday,
            'wednesday' => $this->wednesday,
            'thursday' => $this->thursday,
            'friday' => $this->friday,
            'saturday' => $this->saturday,
            'sunday' => $this->sunday,
            'holidays' => $this->holidays
        ];
    }

    /**
     * Get warehouse summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_of_warehouse,
            'ref_number' => $this->ref_number,
            'location' => [
                'address' => $this->getFullAddress(),
                'coordinates' => $this->getCoordinates(),
                'postal_code' => $this->postalCode
            ],
            'contact' => [
                'phone' => $this->phone
            ],
            'operating_hours' => $this->getOperatingHours(),
            'settings' => [
                'driving_time_correction' => $this->driving_time_correction_factor,
                'daily_driving_limit' => $this->daily_driving_limt,
                'duty_time_limit' => $this->duty_time_limt,
                'run_duration_limit' => $this->run_duration_limt
            ]
        ];
    }

    /**
     * Get all of the comments for the Warehouse
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function territories(): HasMany
    {
        return $this->hasMany(Territory::class, 'warehouse_id', 'id');
    }
}
