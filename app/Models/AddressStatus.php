<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddressStatus extends Model
{
    protected $fillable = [
        'address_status',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'id_address_status');
    }
} 