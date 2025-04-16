<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'id_customer',
        'id_address_type',
        'id_address_status',
        'name',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'address_line_4',
        'address_line_5',
        'postal_reference',
        'country_code',
        'longitude',
        'latitude',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function addressType(): BelongsTo
    {
        return $this->belongsTo(AddressType::class, 'id_address_type');
    }

    public function addressStatus(): BelongsTo
    {
        return $this->belongsTo(AddressStatus::class, 'id_address_status');
    }
} 