<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class OrderLogger
{
    /**
     * Log an order action
     *
     * @param int|null $orderId Order ID
     * @param string $action Action performed
     * @param string $status Current order status
     * @param string|null $eta Estimated time of arrival/completion
     * @return void
     */
    public static function log(
        ?int $orderId,
        string $action,
        string $status,
        ?int $userId = null,
        ?string $eta = null,
        ?string $actionBy = null,
    ): void {
        /*
            We track actions on orders performed by both users and drivers.
            If $actionBy is not null, the action was taken by a driver.
            Otherwise, the action was taken by a user.
            Users exist in the 'user' table, and drivers exist in the 'driver' table.
        */
        if($actionBy != null){
            DB::table('orders_log')->insert([
                'ip' => Request::ip(),
                'proxy' => Request::header('x-forwarded-for'),
                'oid' => $orderId,
                'local' => Request::server('HTTP_HOST'),
                'driver_id' => $userId ?? Auth::id(),
                'actions' => $action,
                'status' => $status,
                'eta' => $eta,
                'timeAndDate' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        else{
            DB::table('orders_log')->insert([
                'ip' => Request::ip(),
                'proxy' => Request::header('x-forwarded-for'),
                'oid' => $orderId,
                'local' => Request::server('HTTP_HOST'),
                'user_id' => $userId ?? Auth::id(),
                'actions' => $action,
                'status' => $status,
                'eta' => $eta,
                'timeAndDate' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
