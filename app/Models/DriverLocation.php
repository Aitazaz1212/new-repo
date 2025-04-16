<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = ['driver_id', 'lat', 'lng' ,'points' ,'route_id'];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
