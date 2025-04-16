<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerFile extends Model
{
    protected $table = 'containerFiles';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'containerID'
    ];
} 