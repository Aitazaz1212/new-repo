<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breaks extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'break_type',
        'break_duration',
        'driving_time_before_break',
        'working_time_before_break',
        'allowed_break_shift'
    ];
}
