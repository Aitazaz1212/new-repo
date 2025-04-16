<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancelledOrder extends Model
{
    public $timestamps = false;
    
    protected $casts = [
        'paid' => 'float',
        'total' => 'float',
        'vatAmount' => 'float',
        'agentOwed' => 'float',
        'rate' => 'float',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'vat_paid' => 'decimal:2',
        'time_stamp' => 'datetime',
        'delete_time' => 'datetime',
        'proforma' => 'integer',
        'showVat' => 'integer',
        'showNotes' => 'integer',
        'tx' => 'integer',
    ];

    protected $fillable = [
        'id_company',
        'id_status',
        'cid',
        'delRoute',
        'endDate',
        'endTime',
        'lastMaintainer',
        'lastMaintainID',
        'orderStatus',
        'paid',
        'startDate',
        'startTime',
        'staffName',
        'staffid',
        'total',
        'dispatchType',
        'collector',
        'colDelDate',
        'carrier',
        'orderType',
        'direct',
        'paymentType',
        'delOrder',
        'authNo',
        'cardNo',
        'exp',
        'companyName',
        'ouref',
        'proforma',
        'showVat',
        'showNotes',
        'serType',
        'icType',
        'iNo',
        'ebayID',
        'vatAmount',
        'zeroVat',
        'ebayStatus',
        'deliverBy',
        'tx',
        'delTime',
        'delStat',
        'agent',
        'agentPaid',
        'agentOwed',
        'rate',
        'txt',
        'code',
        'parcelid',
        'neighbour',
        'aftersales',
        'safePlace',
        'done',
        'ebay',
        'notLoaded',
        'p',
        'wes',
        'cStatus',
        'colOrder',
        'colTime',
        'colRoute',
        'extra',
        'beds',
        'delDate',
        'colID',
        'delID',
        'lat',
        'lng',
        'amazonID',
        'amzShipped',
        'refund',
        'postLoc',
        'post',
        'sender',
        'stamp',
        'startStamp',
        'wholeError',
        'mfError',
        'feedbackScore',
        'delissue',
        'defects',
        'g_id',
        'g_num',
        'g_desc',
        'g_ship',
        'oos',
        'mgmt',
        'processed',
        'paidBeds',
        'pTime',
        'refundBox',
        'id_delivery_route',
        'id_order_type',
        'id_payment_type',
        'hold',
        'missed',
        'urgent',
        'retention',
        'paypal',
        'reopen',
        'id_supplier',
        'paid_supplier',
        'invoice_made',
        'id_seller',
        'seller_paid',
        'discount_amount',
        'discount_rate',
        'vat_paid',
        'paypal_payment',
        'last_trans_id',
        'to_collect',
        'deleted_by',
        'is_split',
        'paid_courier',
        'is_collected',
        'escalate',
        'ordered',
        'merch_ref',
        'trust_pilot',
        'stolen',
        'sales_record'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'id_status');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staffid');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'id_seller');
    }
} 