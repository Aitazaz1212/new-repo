<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = [
        'always_in_stock',
        'id_staff',
        'id_supplier',
        'description',
        'name',
        'staff'
    ];

    protected $casts = [
        'always_in_stock' => 'boolean',
        'id_staff' => 'integer',
        'id_supplier' => 'integer'
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }
} 