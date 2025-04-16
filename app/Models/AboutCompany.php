<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutCompany extends Model
{
    protected $fillable = [
        'global_id',
        'account_name',
        'account_logo',
        'street_address',
        'street_address_line_2',
        'city',
        'state',
        'country',
        'postcode',
        'ph',
        'email',
        'contact_person',
        'contact_position',
        'contact_details',
    ];

    protected $casts = [
        'contact_details' => 'array',
    ];
}
