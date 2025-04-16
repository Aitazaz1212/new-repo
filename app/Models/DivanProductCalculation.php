<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivanProductCalculation extends Model
{
    protected $fillable = [
        'sku',
        'is_parent',
        'configurable_variations',
        'set_code',
        'price',
        'special_price',
        'percentage',
        'storage'
    ];

    protected $casts = [
        'is_parent' => 'integer',
        'configurable_variations' => 'json',
        'price' => 'float',
        'special_price' => 'float',
        'percentage' => 'float'
    ];
}
