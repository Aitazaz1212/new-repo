<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchType extends Model
{
    protected $table = 'dispatch_type';

    public $incrementing = false;

    protected $fillable = [
        'dispatch_type'
    ];
}
