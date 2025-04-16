<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddressType extends Model
{
    protected $fillable = [
        'address_type',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'id_address_type');
    }
} 