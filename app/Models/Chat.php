<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'sender',
        'rcvr',
        'date',
        'msg',
        'seen'
    ];

    protected $casts = [
        'seen' => 'integer'
    ];
} 