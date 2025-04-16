<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\OrderImage;

class Order extends Model
{
    protected $fillable = [
        'id_company',
        'company_order_id',
        'id_status',
        'cid',
        'ref_no',
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
        'is_split',
        'voucher_code',
        'is_shipped',
        'paid_courier',
        'tnt_consignment',
        'ajfoams_tracking_number',
        'bedtatstic_tracking_number',
        'is_collected',
        'escalate',
        'ordered',
        'merch_ref',
        'trust_pilot',
        'stolen',
        'tuffnells',
        'order_number_part_0',
        'order_number_part_1',
        'order_number_part_2',
        'order_number_part_3',
        'order_number_part_4',
        'sales_record',
        'priority',
        'manual_order',
        'delivery_issue',
        'parent_order_id',
        'invoiceStatus',
        'invoiced_time_stamp',
        'invoicePaidStatus',
        'invoicePaid_time_stamp',
        'warehouse_id',
        'vehicle_requirement_id',
        'territory_id',
        'speed_zone_id',
        'description',
        'collection',
        'weight',
        'volume',
        'deliverBy_datetime',
        'error',
        'max_parent_order_id',
        'operation_duration',
        'total_distance',
        'total_duration',
        'locked',
        'stop_sequence',
        'allowNotifications',
        'return_distance',
        'return_duration',
        'loadingTime',
        'waitingTime',
        'workStartTime',
        'returnStartTime',
        'returnWareHouseTime',
        'arriveTime',
        'operationEndTime',
        'runDuration',
        'runTotalDrivingTime',
        'timeViolation',
        'weightExceeded',
        'howMuchWeightOver',
        'volumeExceeded',
        'howMuchVolumeOver',
        'takenloadingTime',
        'takenstartTime',
        'takenwaitingTime',
        'takenworkStartTime',
        'takenreturnStartTime',
        'takenreturnWareHouseTime',
        'takenarriveTime',
        'takenoperationEndTime',
        'takenrunDuration',
        'takenrunTotalDrivingTime',
        'takentimeViolation',
        'takenweightExceeded',
        'takenhowMuchWeightOver',
        'takenvolumeExceeded',
        'takenhowMuchVolumeOver',
        'unLoadingTime',
    ];

    protected $casts = [
        'id_company' => 'integer',
        'company_order_id' => 'integer',
        'id_status' => 'integer',
        'cid' => 'integer',
        'lastMaintainID' => 'integer',
        'paid' => 'float',
        'total' => 'float',
        'staffid' => 'integer',
        'direct' => 'integer',
        'delOrder' => 'integer',
        'proforma' => 'integer',
        'showVat' => 'integer',
        'showNotes' => 'integer',
        'zeroVat' => 'integer',
        'tx' => 'integer',
        'txt' => 'integer',
        'agentPaid' => 'integer',
        'aftersales' => 'integer',
        'done' => 'integer',
        'notLoaded' => 'integer',
        'p' => 'integer',
        'wes' => 'integer',
        'cStatus' => 'integer',
        'colOrder' => 'integer',
        'colID' => 'integer',
        'delID' => 'integer',
        'amzShipped' => 'integer',
        'refund' => 'integer',
        'post' => 'integer',
        'wholeError' => 'integer',
        'mfError' => 'integer',
        'feedbackScore' => 'integer',
        'delissue' => 'integer',
        'defects' => 'integer',
        'oos' => 'integer',
        'mgmt' => 'integer',
        'processed' => 'integer',
        'paidBeds' => 'integer',
        'refundBox' => 'integer',
        'id_delivery_route' => 'integer',
        'id_order_type' => 'integer',
        'id_payment_type' => 'integer',
        'hold' => 'integer',
        'missed' => 'integer',
        'urgent' => 'integer',
        'retention' => 'integer',
        'paypal' => 'integer',
        'reopen' => 'integer',
        'id_supplier' => 'integer',
        'paid_supplier' => 'integer',
        'invoice_made' => 'integer',
        'id_seller' => 'integer',
        'seller_paid' => 'integer',
        'to_collect' => 'integer',
        'is_split' => 'integer',
        'is_shipped' => 'integer',
        'paid_courier' => 'integer',
        'is_collected' => 'integer',
        'escalate' => 'integer',
        'ordered' => 'integer',
        'warehouse_id' => 'integer',
        'error' => 'integer',
        'max_parent_order_id' => 'integer',
        'locked' => 'integer',
        'tuffnells' => 'boolean',
        'manual_order' => 'boolean',
        'delivery_issue' => 'boolean',
        'invoiceStatus' => 'boolean',
        'invoicePaidStatus' => 'boolean',
        'volume' => 'float',
        'total_distance' => 'float',
        'total_duration' => 'float',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'vat_paid' => 'decimal:2',
        'time_stamp' => 'datetime',
        'deliverBy_datetime' => 'datetime',
        'allowNotifications' => 'json',
        'collection' => 'array',
        'weight' => 'array',
        'return_distance' => 'array',
        'return_duration' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $with = ['communicationLogs', 'orderLogs', 'orderAttachments' ,'OrderRejectionDetails'];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'id_status');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cid');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'oid');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class, 'oid');
    }

    public function orderOperationTimeWindow(): HasMany
    {
        return $this->hasMany(OrderOperationTimeWindow::class);
    }

    public function warehouseforpd(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id')->select('id', 'location_of_warehouse', 'name_of_warehouse', 'latitude', 'longitude', 'postalCode');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function vehicleRequirement(): BelongsTo
    {
        return $this->belongsTo(VehicleRequirement::class, 'vehicle_requirement_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_id');
    }

    public function childOrders()
    {
        return $this->hasMany(Order::class, 'max_parent_order_id')
               ->join('disp' ,'disp.oid' ,'orders.id')
               ->orderBy('disp.num', 'asc')
               ->select(self::getOrderSelectFields())
               ->with([
                'customer',
               'orderItems',
               'orderOperationTimeWindow',
               'orderLogs',
               'orderAttachments'
            ]);

    }

    public function dispatchType()
    {
        return $this->belongsTo(DispatchType::class, 'dispatchType');
    }

    public  function disp()
    {
        return $this->hasOne(Disp::class, 'oid', 'id');
    }

    public function orderCustomer()
    {
        return $this->belongsTo(Customer::class, 'cid')->select('id', 'businessName', 'mob', 'email1', 'street', 'web', 'fax as secondContact', 'postcode');
    }

    public function orderItemForApp()
    {
        return $this->hasMany(OrderItem::class, 'oid', 'id')
            ->leftJoin('stock_items', 'orderItems.itemid', 'stock_items.id')
            ->leftJoin('order_item_cancellation_reasons', 'orderItems.rejected_reason', 'order_item_cancellation_reasons.id')
            ->leftJoin('stockDetail', 'stock_items.id', 'stockDetail.sid')
            ->select(
                'orderItems.id',
                'order_item_cancellation_reasons.name as rejectedReasonName',
                'orderItems.qty_scanned',
                'orderItems.comments',
                'orderItems.rejected_reason',
                'orderItems.qty_rejected',
                'orderItems.itemid',
                'orderItems.barcode',
                'orderItems.item_ex_id as external_id',
                'orderItems.Qty as qty',
                'orderItems.oid',
                'orderItems.bedsName as sku',
                \DB::raw('CAST(stockDetail.weight AS CHAR) as weight'),
                \DB::raw('CAST(stockDetail.cbm AS CHAR) as cbm'),
                \DB::raw('CAST(stockDetail.length AS CHAR) as length'),
                \DB::raw('CAST(stockDetail.width AS CHAR) as width'),
                \DB::raw('CAST(stockDetail.height AS CHAR) as height'),
                'stock_items.itemDescription',
                'stock_items.itemName',
                'stock_items.itemCode'
            );
    }

    // get only the images of order type order_completed
    public function OrderImages()
    {
        return $this->hasMany(OrderImage::class, 'order_id', 'id')->where('type', 'order_completed')
            ->select(['id', 'path', 'type', 'order_id']);
    }

    // get all type images of like order_completed , oder_rejected.
    public function orderAttachments()
    {
        return $this->hasMany(OrderImage::class, 'order_id', 'id')
            ->select(['id', 'path', 'type', 'order_id']);
    }
    // Scopes
    public function scopePending($query)
    {
        return $query->where('orderStatus', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('orderStatus', 'completed');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('id_company', $companyId);
    }

    public function scopeNeedsDelivery($query)
    {
        return $query->where('delOrder', 1)
            ->whereNull('delStat');
    }

    public function scopeChildDisp($query)
    {
        return $query->leftJoin('disp', 'disp.oid', '=', 'orders.id');
    }

    // Helper Methods
    public function calculateTotal(): float
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->price * $item->Qty;
        });

        $discountAmount = $subtotal * ($this->discount_rate / 100);
        $total = $subtotal - $discountAmount;

        if ($this->showVat) {
            $total += $total * ($this->vat_paid / 100);
        }

        return $total;
    }

    public function updateStatus(string $newStatus, ?string $notes = null): void
    {
        $oldStatus = $this->orderStatus;
        $this->orderStatus = $newStatus;
        $this->save();

        // Log status change
        OrderStatusHistory::logStatusChange(
            $this->id,
            $oldStatus,
            $newStatus,
            $notes
        );
    }

    public function isFullyPaid(): bool
    {
        return $this->paid >= $this->total;
    }

    public function getRemainingBalance(): float
    {
        return $this->total - $this->paid;
    }

    public function canBeDelivered(): bool
    {
        return !$this->hold
            && !$this->notLoaded
            && $this->delOrder
            && !$this->done;
    }

    public function getFullAddress(): string
    {
        // Implement based on your address fields
        return "Implementation needed";
    }

    public function getOrderNumber(): string
    {
        return implode('', [
            $this->order_number_part_0,
            $this->order_number_part_1,
            $this->order_number_part_2,
            $this->order_number_part_3,
            $this->order_number_part_4
        ]);
    }

    // sub query used in tracking orders
    public function scopeRelatedToTarget($query, $targetOrder)
    {
        $query->where(function ($query) use ($targetOrder) {
            $query->where('orders.id', $targetOrder->id)
                ->orWhere('orders.max_parent_order_id', $targetOrder->id);
        })->orWhere(function ($query) use ($targetOrder) {
            $query->where('orders.id', $targetOrder->max_parent_order_id)
                ->orWhere('orders.max_parent_order_id', $targetOrder->max_parent_order_id);
        });
    }

    /**
     * get the vehicle  data for each order  using the disp disp table ,

     */
    public  function dispvehicle(): HasOne
    {

        return $this->hasOne(Disp::class, 'oid', 'id')->with('vehicleforpd');
    }

    /**
     *  get the order logs , logs are the actions that are taken user on order
     */
    public function orderLogs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'oid', 'id')->with(['user' ,'driver']);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    // Order rejection details
    public function OrderRejectionDetails() :HasMany
    {
        return $this->hasMany(OrderRejectionDetail::class , 'order_id' ,'id')
        ->leftJoin('order_cancellation_reasons' , 'order_rejection_details.reason_id' , 'order_cancellation_reasons.id')
        ->select([
             'order_rejection_details.id' ,
             'order_rejection_details.reason_id' ,
             'order_rejection_details.order_id',
             'order_rejection_details.comments',
             'order_cancellation_reasons.name as reason',
        ]);

    }

    /**
     * Boot the model to apply global scopes.
     */
    protected static function boot()
    {
        parent::boot();

        // Add a global scope to load execution relations
        static::addGlobalScope('withExecutionRelations', function ($query) {
            $query->withExecutionRelations();
        });
    }

    /**
     * Scope to load execution relations dynamically.
     */
    public function scopeWithExecutionRelations($query)
    {
        return $query->with([
            'dispvehicle.vehicleforpd' => function ($query) {
                $query->select('id', 'name', 'driver_id'); // Select specific fields
            },
            'dispvehicle.vehicleforpd.driver' => function ($query) {
                $query->select('id', 'name'); // Select specific fields
            },
            'childOrders.dispvehicle.vehicleforpd' => function ($query) {
                $query->select('id', 'name', 'driver_id'); // Select specific fields
            },
            'childOrders.dispvehicle.vehicleforpd.driver' => function ($query) {
                $query->select('id', 'name'); // Select specific fields
            },
        ]);
    }


      /**
     * Get order select fields
     *
     * @return array<string>
     */
    public static function getOrderSelectFields(): array
    {
        return [
            // Basic Order Information
            "orders.id",
            "orders.ref_no",
            "orders.warehouse_id",
            "orders.priority",
            "orders.vehicle_requirement_id",
            "orders.description",
            "orders.orderType",

             //orders Location and Zone Information
            "orders.speed_zone_id",
            "orders.territory_id",
            "orders.lat",
            "orders.lng",

             //orders Capacity and Measurements
            "orders.weight",
            "orders.volume",

             //orders Status and Configuration
            "orders.allowNotifications",
            "orders.stop_sequence",
            "orders.collection",
            "orders.orderStatus",
            "orders.locked",
            "orders.error",

             //orders Timing Information
            "orders.deliverBy_datetime",
            "orders.operation_duration",
            "orders.startTime",
            "orders.loadingTime",
            "orders.waitingTime",
            "orders.workStartTime",
            "orders.returnStartTime",
            "orders.returnWareHouseTime",
            "orders.arriveTime",
            "orders.operationEndTime",

             //orders Distance and Duration
            "orders.return_duration",
            "orders.return_distance",
            "orders.total_distance",
            "orders.total_duration",

             //orders Run Information
            "orders.runDuration",
            "orders.runTotalDrivingTime",

             //orders Violations and Exceedances
            "orders.timeViolation",
            "orders.weightExceeded",
            "orders.howMuchWeightOver",
            "orders.volumeExceeded",
            "orders.howMuchVolumeOver",

             //orders Relationships
            "orders.max_parent_order_id",
            "orders.parent_order_id",
            "orders.cid",

             //orders Taken Measurements
            "orders.takenloadingTime",
            "orders.takenstartTime",
            "orders.takenwaitingTime",
            "orders.takenworkStartTime",
            "orders.takenreturnStartTime",
            "orders.takenreturnWareHouseTime",
            "orders.takenarriveTime   as ArrivalActual",
            "orders.takenarriveTime   as ArrivalReported",
            "orders.takenoperationEndTime   as ActualJobDuration",
            "orders.completion_status"

        ];
    }
}
