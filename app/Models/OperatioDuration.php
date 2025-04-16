<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatioDuration extends Model
{
    protected $fillable = [
        'fixed_loading_duration',
        'variable_loading_duration_per_unit',
        'fixed_time_per_address',
        'fixed_time_per_order',
        'variable_time_per_capacity_delivery',
        'variable_time_per_capacity_collection',
        'fixed_un_loading_duration',
        'variable_un_loading_duration_per_unit',
        'customer_id'
    ];

    protected $casts = [
        'customer_id' => 'integer'
    ];

    /**
     * Get the customer that owns the operation duration settings.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the total loading duration for a given number of units.
     */
    public function calculateLoadingDuration(int $units): float
    {
        $fixed = (float) $this->fixed_loading_duration;
        $variable = (float) $this->variable_loading_duration_per_unit * $units;
        
        return $fixed + $variable;
    }

    /**
     * Get the total unloading duration for a given number of units.
     */
    public function calculateUnloadingDuration(int $units): float
    {
        $fixed = (float) $this->fixed_un_loading_duration;
        $variable = (float) $this->variable_un_loading_duration_per_unit * $units;
        
        return $fixed + $variable;
    }
} 