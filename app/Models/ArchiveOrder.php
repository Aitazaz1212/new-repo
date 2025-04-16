<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveOrder extends Model
{
    protected $table = 'archiveOrders';

    public $timestamps = false;

    protected $fillable = [
        'cid',
        'orderStatus',
        'startDate',
        'startTime',
        'endDate',
        'endTime',
        'staffName',
        'staffid',
        'paid',
        'total',
        'lastMaintainer',
        'lastMaintainID',
        'delRoute',
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
        'time_stamp',
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
        'wholeError'
    ];

    protected $casts = [
        'paid' => 'float',
        'total' => 'float',
        'vatAmount' => 'float',
        'agentOwed' => 'float',
        'rate' => 'float',
        'time_stamp' => 'datetime'
    ];
}
