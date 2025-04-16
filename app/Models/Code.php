<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    protected $primaryKey = 'pk';

    public $incrementing = false;

    protected $fillable = [
        'lineid',
        'bar',
        'oid',
        'parcelid',
        'itemCode',
        'loaded',
        'warehouse',
        'complete',
        'collected',
        'boxes'
    ];

    protected $casts = [
        'lineid' => 'integer',
        'oid' => 'integer',
        'parcelid' => 'integer',
        'loaded' => 'integer',
        'warehouse' => 'integer',
        'complete' => 'integer',
        'collected' => 'integer',
        'boxes' => 'integer'
    ];
} 