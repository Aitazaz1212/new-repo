<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLocationTimeWindow extends Model
{
    protected $fillable = [
        'customer_location_id',
        'day',
        'start_time',
        'end_time'
    ];

    public function customerLocation(): BelongsTo
    {
        return $this->belongsTo(CustomerLocation::class, 'customer_location_id');
    }
} 