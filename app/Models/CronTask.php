<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronTask extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'value',
        'log'
    ];

    protected $casts = [
        'value' => 'integer'
    ];
} 