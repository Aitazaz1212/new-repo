<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinate extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'pc',
        'lat',
        'lng',
        'street',
        'town'
    ];
} 