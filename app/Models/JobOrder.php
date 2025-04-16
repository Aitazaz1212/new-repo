<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrder extends Model
{
    protected $fillable = [
        'company_id',
        'order_id',
        'duplicate',
        'error_message'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'order_id' => 'integer'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
} 