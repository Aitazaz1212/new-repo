<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'score',
        'uName',
        'pwd',
        'num',
        'rate',
        'user_id',
        'active',
        'comment',
        'external_id',
        'vehicle',
        'cost_per_hour',
        'alerts_for_performers',
        'distribution_centre',
        'territories',
        'start_of_day_location',
        'end_of_day_location',
        'driving_limit',
        'duty_time_limit',
        'run_duration_limit',
        'start_of_day_address',
        'end_of_day_address',
        'email',
        'allow_driving_limit',
        'allow_duty_time_limit',
        'allow_run_duration_limit'
    ];

    protected $casts = [
        'score' => 'integer',
        'rate' => 'decimal:2',
        'user_id' => 'integer',
        'vehicle' => 'integer',
        'distribution_centre' => 'integer',
        'territories' => 'json',
        'active' => 'string',
        'allow_driving_limit' => 'boolean',
        'allow_duty_time_limit' => 'boolean',
        'allow_run_duration_limit' => 'boolean'
    ];

    protected $hidden = [
        'token',
    ];

    protected $appends = [
        'duty_time_limit',
        'driving_limit',
        'run_duration_limit'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'id'  , 'driver_id');
    }

    public function distributionCentre(): BelongsTo
    {
        return $this->belongsTo(DistributionCentre::class, 'distribution_centre');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLoc::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(DriverFcmToken::class);
    }

    public function driverTime(): HasMany
    {
        return $this->hasMany(DriverDay::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'distribution_centre');
    }

    public function driverTerritory()
    {
        return $this->belongsToMany(Territory::class, 'territory_driver', 'driver_id', 'territory_id')
            ->withTimestamps();
    }

    public function driverLocation(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }
    /**
     * fetch the driver routes.
     */
    public function driverRoutes(): HasMany
    {
        return $this->hasMany(DispRoute::class ,'driver_id' , 'id');
    }

    /**
     * To retrieve the driver's vehicle, we typically use the vehicle inverse relationship.
     * However, in some cases, it does not return data due to our database structure.
     * To address this, I created a `hasOne` relationship.
     * I do not want to modify the vehicle inverse relationship, as it may disrupt our functionality.
     */
    public function getvehicle()
    {
        return $this->hasOne(Vehicle::class, 'driver_id', 'id');
    }

    public function getDutyTimeLimitAttribute()
    {
        $time = $this->attributes['duty_time_limit'] ?? '00:00';
        $allowDutyTimeLimit = (bool)($this->attributes['allow_duty_time_limit'] ?? false);
        return ['time' => $time, 'checkbox' => $allowDutyTimeLimit];
    }

    public function getDrivingLimitAttribute()
    {
        $time = $this->attributes['driving_limit'] ?? '00:00';
        $allowDrivingLimit = (bool)($this->attributes['allow_driving_limit'] ?? false);
        return ['time' => $time, 'checkbox' => $allowDrivingLimit];
    }

    public function getRunDurationLimitAttribute()
    {
        $time = $this->attributes['run_duration_limit'] ?? '00:00';
        $allowRunDurationLimit = (bool)($this->attributes['allow_run_duration_limit'] ?? false);
        return ['time' => $time, 'checkbox' => $allowRunDurationLimit];
    }
}

