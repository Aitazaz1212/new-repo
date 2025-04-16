<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Route;

class Disp extends Model
{
    protected $table = 'disp';

    protected $primaryKey = 'id_disp';

    protected $fillable = [
        'oid',
        'date',
        'route',
        'num',
        'van',
        'id_route',
        'height'
    ];

    protected $casts = [
        'oid' => 'integer',
        'num' => 'integer',
        'van' => 'integer',
        'id_route' => 'integer',
        'height' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }

    /**
     *  The orderforApp relationship object are use in the frontend.
     */
    public function orderforApp(): BelongsTo
    {
        return $this->belongsTo(Order::class,'oid','id')->with('items' ,'customer');
    }
    /**
     * Get order form disp.
     */
    public function orderforpd(): BelongsTo
    {
        return $this->belongsTo(Order::class,'oid','id')->with('warehouseforpd');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DispRoute::class, 'id_route');
    }

    public function childOrders()
    {
        return $this->hasMany(Order::class, 'max_parent_order_id', 'oid')->select('id', 'max_parent_order_id', 'orderStatus');
    }

    /**
     *Call hasManythrought relationship ,
     * and then from driver
     *get driverlocation .
     */
    public function driver($routeId = null)
    {
        return $this->hasOneThrough(
            Driver::class,    // Final related model
            Vehicle::class,   // Intermediate model by
            'id',             // Foreign key on Vehicle table (Disp -> van)
            'id',             // Foreign key on Driver table (Vehicle -> Driver)
            'van',            // Local key on Disp table
            'driver_id'       // Local key on Vehicle table
        )->with(['driverLocation']);
    }


    public function vehicleforpd() :BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'van')->with('driver');
    }
}
