<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderAction: string
{
    case CREATED = 'Order Created';
    case UPDATED = 'Order Updated';
    case STATUS_CHANGED = 'Status Changed';
    case ETA_CHANGED = 'ETA Changed';
    case CANCELLED = 'Order Cancelled';
    case COMPLETED = 'Order Completed';
    case PAYMENT_RECEIVED = 'Payment Received';
    case REFUNDED = 'Order Refunded';
}
