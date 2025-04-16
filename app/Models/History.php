<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    protected $fillable = [
        'oid',
        'vehicle_id_previous',
        'vehicle_id_current',
        'id_disp',
        'id_disp_route',
        'action',
        'to',
        'from',
        'user_id'
    ];

    protected $casts = [
        'oid' => 'integer',
        'vehicle_id_previous' => 'integer',
        'vehicle_id_current' => 'integer',
        'id_disp' => 'integer',
        'id_disp_route' => 'integer',
        'action' => 'integer',
        'user_id' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }

    public function previousVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id_previous');
    }

    public function currentVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id_current');
    }

    public function disp(): BelongsTo
    {
        return $this->belongsTo(Disp::class, 'id_disp');
    }

    public function dispRoute(): BelongsTo
    {
        return $this->belongsTo(DispRoute::class, 'id_disp_route');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 