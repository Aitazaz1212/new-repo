<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerB extends Model
{
    protected $table = 'customers_b';
    
    public $timestamps = false;
    
    public $incrementing = false;

    protected $fillable = [
        'accountNo',
        'agent',
        'businessName',
        'email1',
        'email2',
        'email3',
        'fax',
        'mob',
        'number',
        'web',
        'street',
        'tel',
        'town',
        'postcode',
        'dBusinessName',
        'dNumber',
        'dStreet',
        'dTown',
        'dPostcode',
        'discount',
        'id_customer_type',
        'id_seller',
        'isAccount',
        'paymentType',
        'type',
        'owes',
        'userID',
        'company',
        'mail'
    ];

    protected $casts = [
        'agent' => 'integer',
        'discount' => 'float',
        'id_customer_type' => 'integer',
        'id_seller' => 'integer',
        'isAccount' => 'integer',
        'owes' => 'float',
        'mail' => 'integer',
        'timestamp' => 'datetime'
    ];

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'id_customer_type');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'id_seller');
    }
} 