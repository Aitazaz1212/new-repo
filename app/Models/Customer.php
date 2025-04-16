<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    public $timestamps = false;

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
        'mail',
        'numerical_pc',
        'rate',
        'vehicle_requirements',
        'preferred_drivers',
        'description',
        'location_refrence',
        'verified_by',
        'notificationByEmail',
        'notificationBySms'
    ];

    protected $casts = [
        'agent' => 'integer',
        'discount' => 'float',
        'id_customer_type' => 'integer',
        'id_seller' => 'integer',
        'owes' => 'float',
        'mail' => 'integer',
        'numerical_pc' => 'integer',
        'vehicle_requirements' => 'integer',
        'preferred_drivers' => 'json',
        'notificationByEmail' => 'boolean',
        'notificationBySms' => 'boolean',
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

    public function customerTimeWindow()
    {
        return $this->hasMany(CustomerLocationTimeWindow::class, 'customer_location_id', 'id');
    }

    public function operationDuration(): BelongsTo
    {
        return $this->belongsTo(OperationDuration::class, 'operation_duration_id');
    }
}
