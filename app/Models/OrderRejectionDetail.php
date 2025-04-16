<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRejectionDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'reason_id',
        'comments',
    ];

    /**
     * Get the order associated with the rejection detail.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the cancellation reason associated with the rejection detail.
     */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(OrderCancellationReason::class, 'reason_id');
    }
} 