<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerLocation extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'customer_location_reference',
        'description',
        'client_name',
        'primary_telephone_number',
        'secondary_telephone_number',
        'email',
        'website',
        'rate',
        'address',
        'postcode',
        'run_distance_limit',
        'location_is_verified',
        'allow_notifications_by',
        'preferred_drivers',
        'vehicle_requirements',
        'fixed_time_per_address',
        'fixed_time_per_order',
        'variable_time_per_capacity_delivery',
        'variable_time_per_capacity_collection'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeWindows(): HasMany
    {
        return $this->hasMany(CustomerLocationTimeWindow::class);
    }
} 