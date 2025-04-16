<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    protected $table = 'customer_type';

    public $incrementing = false;

    protected $fillable = [
        'customer_type'
    ];
} 