<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Col extends Model
{
    protected $table = 'col';

    protected $fillable = [
        'oid',
        'date',
        'route',
        'num'
    ];

    protected $casts = [
        'oid' => 'integer',
        'num' => 'integer'
    ];
} 