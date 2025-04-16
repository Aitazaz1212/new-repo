<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flagged extends Model
{
    protected $fillable = [
        'oid',
        'reason'
    ];

    protected $casts = [
        'oid' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }
} 