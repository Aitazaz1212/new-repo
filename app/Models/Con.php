<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Con extends Model
{
    protected $table = 'cons';

    public $incrementing = false;

    protected $fillable = [
        'consid',
        'deliverTo',
        'town',
        'postcode',
        'pieces',
        'weight',
        'type',
        'date',
        'status',
        'stamp',
        'to',
        'sender',
        'parcelid',
        'price',
        'paid',
        'oid',
        'delDate',
        'colID',
        'hide'
    ];

    protected $casts = [
        'pieces' => 'integer',
        'weight' => 'float',
        'to' => 'integer',
        'sender' => 'integer',
        'parcelid' => 'integer',
        'price' => 'float',
        'paid' => 'float',
        'oid' => 'integer',
        'colID' => 'integer',
        'hide' => 'integer'
    ];
} 