<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    public const TYPE_EBAY = 'ebay';
    public const TYPE_COX_AND_COX = 'cox-and-cox';
    public const TYPE_GENERAL = 'general';

    protected $fillable = [
        'key',
        'type',
        'value',
        'order_by',
        'details'
    ];

    protected $casts = [
        'order_by' => 'integer',
        'value' => 'string',
        'details' => 'string'
    ];
} 