<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dictionary extends Model
{
    protected $table = 'dictionary';
    
    protected $primaryKey = 'name';
    
    public $incrementing = false;
    
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'value'
    ];

    protected $attributes = [
        'name' => ''
    ];
} 