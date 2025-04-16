<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $table = 'counter';

    protected $fillable = [
        'num'
    ];

    protected $casts = [
        'num' => 'integer'
    ];
} 