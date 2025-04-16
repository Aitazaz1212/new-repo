<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery2 extends Model
{
    protected $table = 'delivery';
    
    public $incrementing = false;

    protected $fillable = [
        'I'
    ];
} 