<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    protected $table = 'camp';

    protected $fillable = [
        'name',
        'sent',
        'not_sent',
        'date'
    ];

    protected $casts = [
        'sent' => 'integer',
        'not_sent' => 'integer'
    ];
} 