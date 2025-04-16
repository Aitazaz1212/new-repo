<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispRoute extends Model
{
    protected $table = 'dispRoute';

    public const STATUS_ROUTED = 'Routed';

    protected $fillable = [
        'date',
        'driver',
        'driverPaid',
        'mate',
        'matePaid',
        'picker',
        'payments',
        'route',
        'staff',
        'startTime',
        'status',
        'toCollect',
        'vehicle',
        'id_route',
        'loader',
        'driver_id',
        'endTime',
        'exp_delivery_time'
    ];

    protected $casts = [
        'driverPaid' => 'integer',
        'matePaid' => 'integer',
        'payments' => 'float',
        'toCollect' => 'float',
        'id_route' => 'integer',
        'driver_id' => 'integer',
        'endTime' => 'datetime',
        'exp_delivery_time' => 'datetime'
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'id_route');
    }

    public function driverRelation(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function routewithfirstorder()
    {
        return $this->hasOne(Disp::class,'id_route','id')->where('num' , '0')->with('childOrders');
    }

    /**
     * Each route contain multiples disp records , and each disp is connected to the order.
     */
    public function disp() : HasMany
    {
        return $this->hasMany(Disp::class,'id_route','id');
    }
}
