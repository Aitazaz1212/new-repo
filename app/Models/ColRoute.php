<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColRoute extends Model
{
    protected $table = 'colRoute';

    public $incrementing = false;

    protected $fillable = [
        'route',
        'date',
        'driver',
        'staff',
        'status',
        'mate',
        'vehicle',
        'payments',
        'toCollect'
    ];

    protected $casts = [
        'payments' => 'float',
        'toCollect' => 'float'
    ];
}
