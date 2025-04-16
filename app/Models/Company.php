<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    public $incrementing = false;

    protected $fillable = [
        'company_name',
        'id_email',
        'l_id_email',
        'importer_id'
    ];

    protected $casts = [
        'id_email' => 'integer',
        'l_id_email' => 'integer',
        'importer_id' => 'integer'
    ];
} 