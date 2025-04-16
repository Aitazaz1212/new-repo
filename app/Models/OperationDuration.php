<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationDuration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'fixed_loading_duration',
        'variable_loading_duration_per_unit',
        'fixed_time_per_address',
        'fixed_time_per_order',
        'variable_time_per_capacity_delivery',
        'variable_time_per_capacity_collection',
        'fixed_un_loading_duration',
        'variable_un_loading_duration_per_unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fixed_time_per_order' => 'object',
        'fixed_time_per_address' => 'object',
        'variable_time_per_capacity_delivery' => 'object',
        'variable_time_per_capacity_collection' => 'object',
    ];

    /**
     * Get the customer that owns the operation duration.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}