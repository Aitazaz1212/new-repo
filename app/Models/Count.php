<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Count extends Model
{
    protected $table = 'count';

    protected $fillable = [
        'num',
        'parcel_id_seed',
        'orders_count',
        'refund',
        'post',
        'exchange'
    ];

    protected $casts = [
        'num' => 'integer',
        'parcel_id_seed' => 'integer',
        'orders_count' => 'integer',
        'refund' => 'integer',
        'post' => 'integer',
        'exchange' => 'integer'
    ];
}
