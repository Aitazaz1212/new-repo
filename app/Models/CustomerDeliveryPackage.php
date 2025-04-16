<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeliveryPackage extends Model
{
    protected $table = 'customer_delivery_package';

    protected $fillable = [
        'oid',
        'package_id',
        'sub_package_id',
        'amount',
        'paid',
        'stripe_payment_id',
        'currency',
        'payment_status',
        'user_id'
    ];

    protected $casts = [
        'package_id' => 'integer',
        'sub_package_id' => 'integer',
        'amount' => 'decimal:2',
        'paid' => 'boolean',
        'user_id' => 'integer'
    ];

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const CURRENCY_GBP = 'GBP';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function subPackage(): BelongsTo
    {
        return $this->belongsTo(SubPackage::class);
    }
} 