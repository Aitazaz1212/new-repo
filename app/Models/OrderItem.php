<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'orderItems';

    protected $fillable = [
        'id_staff',
        'id_item_status',
        'itemid',
        'oid',
        'bedsName',
        'blocked',
        'currStock',
        'discount',
        'itemStatus',
        'lineTotal',
        'notes',
        'price',
        'Qty',
        'staffName',
        'costs',
        'barcode',
        'item_ex_id',
        'item_actual_quantity',
        'status',
        'is_rejected',
        'rejected_reason',
        'comments',
        'qty_rejected',
        'qty_scanned'
    ];

    protected $casts = [
        'id_staff' => 'integer',
        'id_item_status' => 'integer',
        'itemid' => 'integer',
        'oid' => 'integer',
        'blocked' => 'integer',
        'currStock' => 'integer',
        'discount' => 'float',
        'lineTotal' => 'float',
        'price' => 'decimal:2',
        'Qty' => 'integer',
        'costs' => 'float',
        'item_actual_quantity' => 'integer',
        'is_rejected' => 'boolean',
        'qty_rejected' => 'integer',
        'qty_scanned' => 'integer'
    ];

    public function itemImages()
    {
        return $this->hasMany(OrderItemRejectedImage::class, 'order_item_id' , 'id');
    }


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'itemid');
    }

    public function itemStatus(): BelongsTo
    {
        return $this->belongsTo(ItemStatus::class, 'id_item_status');
    }

    /**
     * Calculate the total price after discount.
     */
    public function getTotalAfterDiscount(): float
    {
        $total = $this->price * $this->Qty;
        return $total - ($total * ($this->discount / 100));
    }

    /**
     * Check if the item has been fully scanned.
     */
    public function isFullyScanned(): bool
    {
        return $this->qty_scanned === $this->Qty;
    }

    /**
     * Get the remaining quantity to be scanned.
     */
    public function getRemainingQuantity(): int
    {
        return $this->Qty - $this->qty_scanned;
    }

    /**
     * Get the orderItem details
    */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'itemid');
    }

}
