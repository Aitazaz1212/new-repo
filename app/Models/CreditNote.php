<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'amount',
        'order_id',
        'seller_id',
        'user_id',
        'used'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used' => 'decimal:2',
        'order_id' => 'integer',
        'seller_id' => 'integer',
        'user_id' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
} 